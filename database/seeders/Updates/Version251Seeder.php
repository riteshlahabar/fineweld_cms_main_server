<?php

namespace Database\Seeders\Updates;

use App\Enums\Item;
use App\Enums\ItemTransactionUniqueCode;
use App\Models\Items\ItemBatchMaster;
use App\Models\Items\ItemBatchQuantity;
use App\Models\Items\ItemBatchTransaction;
use App\Models\Items\ItemGeneralQuantity;
use App\Models\Items\ItemTransaction;
use App\Services\ItemService;
use App\Services\ItemTransactionService;
use Illuminate\Database\Seeder;

class Version251Seeder extends Seeder
{
    public $itemTransactionService;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        echo 'Version251Seeder Running...';

        $this->updatePermissions();
        $this->addNewPermissions();

        echo "\Version251Seeder Completed!!\n";
    }

    public function updatePermissions()
    {
        // I wanted to get all items where tracking_type = batch and wanted to run ItemTransactionService::updateItemBatchQuantityWarehouseWise
        $items = \App\Models\Items\Item::where('tracking_type', 'batch')->get();

        if (! count($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item->count() <= 0) {
                continue;
            }

            $batches = \App\Models\Items\ItemBatchMaster::where('item_id', $item->id)->get();
            foreach ($batches as $batch) {
                $this->updateItemBatchQuantityWarehouseWise($batch->id);
            }
        }
    }

    public function addNewPermissions()
    {
        //
    }// funciton addNewPermissions

    public function updateItemBatchQuantityWarehouseWise($itemBatchMasterId)
    {
        // Delete Reords from ItemBatchQuantity
        ItemBatchQuantity::where('item_batch_master_id', $itemBatchMasterId)->delete();

        $itemBatchTransactions = ItemBatchTransaction::selectRaw('
                                    COALESCE(SUM(
                                    CASE
                                        WHEN item_transactions.unique_code IN (
                                        "'.ItemTransactionUniqueCode::PURCHASE->value.'",
                                        "'.ItemTransactionUniqueCode::SALE_RETURN->value.'",
                                        "'.ItemTransactionUniqueCode::ITEM_OPENING->value.'",
                                        "'.ItemTransactionUniqueCode::STOCK_RECEIVE->value.'",
                                        "'.ItemTransactionUniqueCode::STOCK_ADJUSTMENT_INCREASE->value.'"
                                        ) THEN
                                        CASE
                                            WHEN items.base_unit_id = item_transactions.unit_id THEN item_batch_transactions.quantity
                                            WHEN items.secondary_unit_id = item_transactions.unit_id THEN item_batch_transactions.quantity / items.conversion_rate
                                            ELSE 0
                                        END
                                        WHEN item_transactions.unique_code IN (
                                        "'.ItemTransactionUniqueCode::PURCHASE_RETURN->value.'",
                                        "'.ItemTransactionUniqueCode::SALE->value.'",
                                        "'.ItemTransactionUniqueCode::STOCK_TRANSFER->value.'",
                                        "'.ItemTransactionUniqueCode::STOCK_ADJUSTMENT_DECREASE->value.'"
                                        ) THEN
                                        CASE
                                            WHEN items.base_unit_id = item_transactions.unit_id THEN -item_batch_transactions.quantity
                                            WHEN items.secondary_unit_id = item_transactions.unit_id THEN -item_batch_transactions.quantity / items.conversion_rate
                                            ELSE 0
                                        END
                                        ELSE 0
                                    END
                                    ), 0) AS item_batch_warehouse_stock,
                                    item_batch_transactions.item_id,
                                    item_batch_transactions.warehouse_id,
                                    item_batch_transactions.item_batch_master_id
                                ')
            ->join('item_transactions', 'item_batch_transactions.item_transaction_id', '=', 'item_transactions.id')
            ->join('items', 'item_batch_transactions.item_id', '=', 'items.id')
            ->where('item_batch_transactions.item_batch_master_id', $itemBatchMasterId)
            ->whereNotIn('item_transactions.unique_code', [
                ItemTransactionUniqueCode::PURCHASE_ORDER->value,
                ItemTransactionUniqueCode::SALE_ORDER->value,
            ])
            ->groupBy('item_batch_transactions.item_id', 'item_batch_transactions.warehouse_id', 'item_batch_transactions.item_batch_master_id')
            ->get();

        if ($itemBatchTransactions->isNotEmpty()) {

            // Collection Group by warehouse
            $itemBatchTransactions = $itemBatchTransactions->groupBy('warehouse_id')->toArray();

            // MULTIPLE SERIAL TRANSACTIONS
            foreach ($itemBatchTransactions as $warehouseId => $batchTransactions) {
                foreach ($batchTransactions as $itemBatchTransaction) {
                    // Record ItemBatchQuantity
                    $readyData = [
                        'item_id' => $itemBatchTransaction['item_id'],
                        'warehouse_id' => $warehouseId,
                        'item_batch_master_id' => $itemBatchTransaction['item_batch_master_id'],
                        'quantity' => $itemBatchTransaction['item_batch_warehouse_stock'],
                    ];

                    $created = ItemBatchQuantity::create($readyData);
                    if (! $created) {
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }
                }// foreach itemBatchTransaction
            }
        }// count>0 itemBatchTransaction

        // Find the item id
        $itemId = ItemBatchMaster::where('id', $itemBatchMasterId)->first()->item_id;

        /**
         * Record Item All
         * */
        $updateQuantityWarehouseWise = $this->updateItemGeneralQuantityWarehouseWise($itemId, new ItemService);
        if (! $updateQuantityWarehouseWise) {
            throw new \Exception('Failed to record General Items Stock Warehouse Wise!');
        }

        return true;
    }

    public function updateItemGeneralQuantityWarehouseWise($itemGeneralMasterId, $itemService)
    {

        $itemTransactions = ItemTransaction::selectRaw('
                                    COALESCE(SUM(
                                        CASE
                                            WHEN unique_code IN (
                                                "'.ItemTransactionUniqueCode::PURCHASE->value.'",
                                                "'.ItemTransactionUniqueCode::SALE_RETURN->value.'",
                                                "'.ItemTransactionUniqueCode::ITEM_OPENING->value.'",
                                                "'.ItemTransactionUniqueCode::STOCK_RECEIVE->value.'",
                                                "'.ItemTransactionUniqueCode::STOCK_ADJUSTMENT_INCREASE->value.'"
                                            ) THEN
                                                CASE
                                                    WHEN items.base_unit_id = item_transactions.unit_id THEN quantity
                                                    WHEN items.secondary_unit_id = item_transactions.unit_id THEN quantity / items.conversion_rate
                                                    ELSE 0
                                                END
                                            WHEN unique_code IN (
                                                "'.ItemTransactionUniqueCode::PURCHASE_RETURN->value.'",
                                                "'.ItemTransactionUniqueCode::SALE->value.'",
                                                "'.ItemTransactionUniqueCode::STOCK_TRANSFER->value.'",
                                                "'.ItemTransactionUniqueCode::STOCK_ADJUSTMENT_DECREASE->value.'"
                                            ) THEN
                                                CASE
                                                    WHEN items.base_unit_id = item_transactions.unit_id THEN -quantity
                                                    WHEN items.secondary_unit_id = item_transactions.unit_id THEN -quantity / items.conversion_rate
                                                    ELSE 0
                                                END
                                            ELSE 0
                                        END
                                    ), 0) AS item_general_warehouse_stock,
                                    item_id,
                                    warehouse_id
                                ')
            ->join('items', 'item_transactions.item_id', '=', 'items.id')
            ->whereNotIn('unique_code', [
                ItemTransactionUniqueCode::PURCHASE_ORDER->value,
                ItemTransactionUniqueCode::SALE_ORDER->value,
            ])
            ->where('item_id', $itemGeneralMasterId)
            ->groupBy('item_id', 'warehouse_id')
            ->get();

        // Delete ItemGeneralQuantity
        ItemGeneralQuantity::where('item_id', $itemGeneralMasterId)->delete();

        if ($itemTransactions->count() > 0) {

            // Group By warehouse
            $itemGeneralTransactions = $itemTransactions->groupBy('warehouse_id')->toArray();

            // MULTIPLE ITEM TRANSACTIONS
            foreach ($itemGeneralTransactions as $warehouseId => $generalransactions) {
                foreach ($generalransactions as $generalransaction) {
                    // Record ItemGeneralQuantity
                    $readyData = [
                        'item_id' => $generalransaction['item_id'],
                        'warehouse_id' => $warehouseId,
                        'quantity' => $generalransaction['item_general_warehouse_stock'],
                    ];

                    $created = ItemGeneralQuantity::create($readyData);
                    if (! $created) {
                        throw new \Exception('Failed to record General Items Warehouse Wise!');
                    }

                    /**
                     * Update Item Master Stock
                     * */
                    $updateStock = $itemService->updateItemStock($itemGeneralMasterId);
                    if (! $updateStock) {
                        throw new \Exception('Failed to update Item Master Stock!!');
                    }
                }// foreach generalransactions
            }
        }

        return true;
    }
}
