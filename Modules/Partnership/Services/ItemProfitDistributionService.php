<?php

namespace Modules\Partnership\Services;

use App\Models\Sale\Sale;
use App\Models\Sale\SaleReturn;
use App\Services\ItemTransactionService;
use Illuminate\Support\Facades\DB;
use Modules\Partnership\Http\Models\ContractItem;
use Modules\Partnership\Http\Models\ItemProfit;
use Modules\Partnership\Http\Models\Partner;
use Modules\Partnership\Http\Models\PartnerProfitShare;

class ItemProfitDistributionService
{
    private $itemTransactionService;

    public $isPositiveProfit = true;

    public $transactionOf = null;

    public $actuallNetProfit = 0;

    /**
     * Create the event listener.
     */
    public function __construct(ItemTransactionService $itemTransactionService)
    {
        $this->itemTransactionService = $itemTransactionService;
    }

    // Sale Return Profit Recording
    public function recordSaleReturnProfit($returnId)
    {

        $this->transactionOf = 'sale_return';

        $return = SaleReturn::find($returnId);
        $returnItems = $return->itemTransaction;

        $return->date = $return->return_date;

        ItemProfit::where('sale_return_id', $return->id)->delete();

        $this->distributeProfitToPartners($return, $returnItems);

    }

    // Sale Profit Recording
    public function recordSaleProfit($saleId)
    {

        $this->transactionOf = 'sale';

        $sale = Sale::find($saleId);
        $modelItems = $sale->itemTransaction;

        $sale->date = $sale->sale_date;

        $sale->existingItemProfits = ItemProfit::where('sale_id', $sale->id)->get();

        ItemProfit::where('sale_id', $sale->id)->delete();

        $this->distributeProfitToPartners($sale, $modelItems);
    }

    public function distributeProfitToPartners($model, $modelItems)
    {
        if ($this->transactionOf == null) {
            throw new \Exception('Transaction type is not defined.');
        }

        $itemsIds = $modelItems->pluck('item_id')->unique()->toArray();

        if (count($itemsIds) == 0) {
            return;
        }

        // Get Item Average Purchase Price
        $itemsAvgPrice = $this->itemTransactionService->calculateEachItemSaleAndPurchasePrice($itemsIds, warehouseId: null, useGlobalPurchasePrice: true);

        /**
         * Used to find the latest contract item per partner and item
         *
         */
        $contractItems = ContractItem::query()
            ->join(DB::raw('(
                SELECT partner_id, item_id,
                    MAX(CONCAT(contract_date, LPAD(id, 10, "0"))) AS latest_key
                FROM contract_items
                WHERE contract_date <= "'.$model->date.'"
                GROUP BY partner_id, item_id
            ) latest'), function ($join) {
                $join->on('latest.partner_id', '=', 'contract_items.partner_id')
                    ->on('latest.item_id', '=', 'contract_items.item_id')
                    ->on(DB::raw('latest.latest_key'), '=', DB::raw('CONCAT(contract_items.contract_date, LPAD(contract_items.id, 10, "0"))'));
            })
            ->where('contract_items.contract_date', '<=', $model->date)
            ->select('contract_items.*')
            ->get();

        // Get the default partner id
        $defaultPartnerId = Partner::where('default_partner', 1)->first()?->id;

        if (is_null($defaultPartnerId)) {
            throw new \Exception('Default partner is not set. Please set a default partner to proceed.');
        }

        // Record sale profit of each item and distribute to partners
        foreach ($modelItems as $modelItem) {

            $totalProfit = $modelItem->total - (
                ($itemsAvgPrice[$modelItem->item_id]['purchase']['average_purchase_price'] ?? 0) * $modelItem->quantity
            );
            if ($this->transactionOf == 'sale') {
                if ($totalProfit < 0) {
                    // Sale: If negative then it should be negative
                    // $this->isPositiveProfit = false;
                    $totalProfit = abs($totalProfit);
                    $netProfit = -$totalProfit;
                } else {
                    // Sale: If positive then it should be positive
                    $netProfit = $totalProfit;
                }

            } elseif ($this->transactionOf == 'sale_return') {
                if ($totalProfit > 0) {
                    // Sale Return: convert positive value into negative
                    // $this->isPositiveProfit = false;
                    $netProfit = -$totalProfit;
                } else {
                    // Sale Return: convert negative value into positive
                    $netProfit = abs($totalProfit);
                }
            } else {
                throw new \Exception('Transaction type is not defined.');
            }

            $this->actuallNetProfit = $netProfit;

            // Create sale profit record
            $ItemProfit = ItemProfit::create([
                'transaction_date' => $model->date,
                'sale_id' => ($this->transactionOf == 'sale') ? $model->id : null,
                'sale_return_id' => ($this->transactionOf == 'sale_return') ? $model->id : null,
                'item_id' => $modelItem->item_id,
                'purchase_price' => $itemsAvgPrice[$modelItem->item_id]['purchase']['average_purchase_price'] ?? 0,
                'unit_price' => $modelItem->unit_price,
                'tax_amount' => $modelItem->tax_amount,
                'discount_amount' => $modelItem->discount_amount,
                'quantity' => $modelItem->quantity,
                'total' => $modelItem->total,
                'gross_profit' => $modelItem->total + $modelItem->tax_amount - $modelItem->discount_amount,
                'net_profit' => $netProfit,
            ]);

            // Find related contract items for this sale item
            $relatedContractItems = $contractItems->where('item_id', $modelItem->item_id);

            // If no contract found for the item, assign 100% profit to default partner
            if ($relatedContractItems->isEmpty()) {
                $this->recordPartnerProfitShare($ItemProfit, [
                    'transaction_date' => $model->date,
                    'sale_id' => ($this->transactionOf == 'sale') ? $model->id : null,
                    'sale_return_id' => ($this->transactionOf == 'sale_return') ? $model->id : null,
                    'item_profit_id' => $ItemProfit->id,
                    'partner_id' => $defaultPartnerId,
                    'contract_id' => null,
                    'item_id' => $modelItem->item_id,
                    'share_percentage' => 100,
                    'distributed_profit_amount' => $netProfit, // $totalProfit,//based on the
                ]);

                continue;
            }

            // If contract items found, distribute profit accordingly (percentage only)
            $this->distributeProfilePercentage(
                $ItemProfit,
                $modelItem->item_id,
                $relatedContractItems,
                $totalProfit,
                $defaultPartnerId,
                $model
            );
        }
    }

    /**
     * Distribute profit among partners based on percentage shares
     *
     * @param  int  $ItemProfitId
     * @param  int  $itemId
     * @param  Collection  $contractItems
     * @param  float  $totalProfit
     * @param  int  $defaultPartnerId
     */
    private function distributeProfilePercentage($ItemProfit, $itemId, $contractItems, $totalProfit, $defaultPartnerId, $model): void
    {
        // Step 1: Validate total percentage doesn't exceed 100%
        $totalPercentage = $contractItems->sum('share_value');

        if ($totalPercentage > 100) {
            throw new \Exception(
                "Total percentage for item {$itemId} is {$totalPercentage}%. ".
                'Cannot exceed 100%. Please adjust contract partner shares.'
            );
        }

        $totalAssigned = 0;

        // Step 2: Distribute profit to each partner based on their percentage
        foreach ($contractItems as $contractItem) {
            $sharePercentage = floatval($contractItem->share_value ?? 0);

            if ($sharePercentage <= 0) {
                continue;
            }

            // Calculate partner's share (percentage of total profit)
            $partnerProfit = ($totalProfit * $sharePercentage) / 100.0;

            // Record partner profit share
            $this->recordPartnerProfitShare($ItemProfit, [
                'transaction_date' => $model->date,
                'sale_id' => ($this->transactionOf == 'sale') ? $model->id : null,
                'sale_return_id' => ($this->transactionOf == 'sale_return') ? $model->id : null,
                'item_profit_id' => $ItemProfit->id,
                'partner_id' => $contractItem->partner_id,
                'contract_id' => $contractItem->contract_id,
                'item_id' => $itemId,
                'share_percentage' => $sharePercentage,
                'distributed_profit_amount' => $partnerProfit,
            ]);

            $totalAssigned += $partnerProfit;
        }

        // Step 3: Assign remaining profit to default partner
        $remainingProfit = $totalProfit - $totalAssigned;

        if ($remainingProfit > 0.01) { // Allow for floating point precision
            $remainingPercentage = 100 - $totalPercentage;

            $this->recordPartnerProfitShare($ItemProfit, [
                'transaction_date' => $model->date,
                'sale_id' => ($this->transactionOf == 'sale') ? $model->id : null,
                'sale_return_id' => ($this->transactionOf == 'sale_return') ? $model->id : null,
                'item_profit_id' => $ItemProfit->id,
                'partner_id' => $defaultPartnerId,
                'contract_id' => null,
                'item_id' => $itemId,
                'share_percentage' => $remainingPercentage,
                'distributed_profit_amount' => $remainingProfit,
            ]);
        }
    }

    /**
     * Record partner profit share to database
     *
     * @throws \Exception
     */
    public function recordPartnerProfitShare(ItemProfit $ItemProfit, array $data): PartnerProfitShare
    {
        try {

            $record = $ItemProfit->profitTransaction()->create([
                'transaction_date' => $data['transaction_date'],
                'sale_id' => $data['sale_id'],
                'sale_return_id' => $data['sale_return_id'],
                'item_profit_id' => $data['item_profit_id'],
                'item_id' => $data['item_id'],
                'partner_id' => $data['partner_id'],
                'contract_id' => $data['contract_id'] ?? null,
                'share_type' => 'percentage',
                'share_value' => $data['share_percentage'],
                'distributed_profit_amount' => ($this->actuallNetProfit >= 0) ? $data['distributed_profit_amount'] : -$data['distributed_profit_amount'],
            ]);

            if (! $record) {
                throw new \Exception('Failed to create partner profit share record.');
            }

            return $record;
        } catch (\Exception $e) {
            throw new \Exception('Failed to record partner profit share: '.$e->getMessage());
        }
    }
}
