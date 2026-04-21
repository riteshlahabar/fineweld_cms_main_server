<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Items\ItemBatchQuantity;
use App\Models\Items\ItemGeneralQuantity;
use App\Models\Items\ItemSerialQuantity;
use App\Models\User;
use App\Services\ItemService;
use App\Services\ItemTransactionService;
use App\Services\StockImpact;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    private $stockImpact;

    private $itemTransactionService;

    public function __construct(StockImpact $stockImpact, ItemTransactionService $itemTransactionService, public ItemService $itemService)
    {
        $this->stockImpact = $stockImpact;
        $this->itemTransactionService = $itemTransactionService;
    }

    /**
     * Report -> Stock Report -> Serial
     * */
    public function getBatchWiseStockRecords(Request $request): JsonResponse
    {
        try {
            $itemId = $request->input('item_id');
            $brandId = $request->input('brand_id');
            $batchMasterId = $request->input('batch_id');
            $warehouseId = $request->input('warehouse_id');

            // If warehouseId is not provided, fetch warehouses accessible to the user
            $warehouseIds = $warehouseId ? [$warehouseId] : User::find(auth()->id())->getAccessibleWarehouses()->pluck('id');

            $preparedData = ItemBatchQuantity::with('itemBatchMaster.item')
                ->when($batchMasterId, function ($query) use ($batchMasterId) {
                    $query->where('item_batch_master_id', $batchMasterId);
                })
                ->whereIn('warehouse_id', $warehouseIds)
                ->when($itemId, function ($query) use ($itemId) {
                    $query->where('item_id', $itemId);
                })
                ->when($brandId, function ($query) use ($brandId) {
                    return $query->whereHas('itemBatchMaster.item', function ($query) use ($brandId) {
                        $query->where('brand_id', $brandId); // Corrected to `brand_id`
                    });
                })
                ->get();

            if ($preparedData->count() == 0) {
                throw new \Exception('No Records Found!!');
            }

            $recordsArray = [];

            foreach ($preparedData as $data) {

                $availableStockWarehouseWise = ItemBatchQuantity::where('item_id', $data->item_id)
                    ->where('warehouse_id', $data->warehouse_id)
                    ->where('item_batch_master_id', $data->item_batch_master_id)
                    ->sum('quantity');

                $remainingDays = $this->itemTransactionService->daysDifferenceByDate($data->itemBatchMaster->exp_date);
                $recordsArray[] = [
                    'warehouse' => $data->warehouse->name,
                    'item_name' => $data->itemBatchMaster->item->name,
                    'brand_name' => $data->itemBatchMaster->item->brand->name ?? '',
                    'batch_no' => $data->itemBatchMaster->batch_no ?? '',
                    'mfg_date' => $data->itemBatchMaster->formatted_mfg_date ?? '',
                    'exp_date' => $data->itemBatchMaster->formatted_exp_date ?? '',
                    'days_until_expiry' => $remainingDays,
                    'model_no' => $data->itemBatchMaster->model_no ?? '',
                    'color' => $data->itemBatchMaster->color ?? '',
                    'size' => $data->itemBatchMaster->size ?? '',
                    'quantity' => $availableStockWarehouseWise, // $data->quantity,
                    'stock_impact_color' => ($remainingDays <= 0) ? 'danger' : '',
                    'stock_color' => ($availableStockWarehouseWise <= 0) ? 'danger' : '',
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Records are retrieved!!',
                'data' => $recordsArray,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);

        }
    }

    /**
     * Report -> Stock Report -> Serial
     * */
    public function getSerialWiseStockRecords(Request $request): JsonResponse
    {
        try {
            $itemId = $request->input('item_id');
            $brandId = $request->input('brand_id');
            $serialMasterId = $request->input('serial_id');
            $warehouseId = $request->input('warehouse_id');

            // If warehouseId is not provided, fetch warehouses accessible to the user
            $warehouseIds = $warehouseId ? [$warehouseId] : User::find(auth()->id())->getAccessibleWarehouses()->pluck('id');

            $preparedData = ItemSerialQuantity::with('itemSerialMaster')
                ->when($serialMasterId, function ($query) use ($serialMasterId) {
                    $query->where('item_serial_master_id', $serialMasterId); // Corrected to 'id'
                })
                ->whereIn('warehouse_id', $warehouseIds)
                ->when($itemId, function ($query) use ($itemId) {
                    $query->where('item_id', $itemId);
                })
                ->when($brandId, function ($query) use ($brandId) {
                    return $query->whereHas('itemSerialMaster.item', function ($query) use ($brandId) {
                        $query->where('brand_id', $brandId); // Corrected to `brand_id`
                    });
                })
                ->get();

            if ($preparedData->count() == 0) {
                throw new \Exception('No Records Found!!');
            }

            $recordsArray = [];

            foreach ($preparedData as $data) {
                $recordsArray[] = [
                    'warehouse' => $data->warehouse->name,
                    'item_name' => $data->itemSerialMaster->item->name,
                    'brand_name' => $data->itemSerialMaster->item->brand->name ?? '',
                    'serial_code' => $data->itemSerialMaster->serial_code ?? '',
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Records are retrieved!!',
                'data' => $recordsArray,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);

        }
    }

    public function getGeneralStockRecords(Request $request): JsonResponse
    {
        // try{
        $itemId = $request->input('item_id');
        $brandId = $request->input('brand_id');
        $categoryId = $request->input('item_category_id');
        $warehouseId = $request->input('warehouse_id');

        // If warehouseId is not provided, fetch warehouses accessible to the user
        $warehouseIds = $warehouseId ? [$warehouseId] : User::find(auth()->id())->getAccessibleWarehouses()->pluck('id');

        $preparedData = ItemGeneralQuantity::with('item', 'warehouse')
            ->when($itemId, function ($query) use ($itemId) {
                return $query->where('item_id', $itemId);
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                return $query->whereHas('item', function ($query) use ($categoryId) {
                    return $query->where('item_category_id', $categoryId);
                });
            })
            ->when($brandId, function ($query) use ($brandId) {
                return $query->whereHas('item', function ($query) use ($brandId) {
                    $query->where('brand_id', $brandId);
                });
            })
            ->whereIn('warehouse_id', $warehouseIds)
            ->get();

        if ($preparedData->count() == 0) {
            throw new \Exception('No Records Found!!');
        }

        $recordsArray = [];

        // Show warehouse-wise records if no warehouse is selected
        foreach ($preparedData as $data) {
            $currentStock = ItemGeneralQuantity::where('item_id', $data->item_id)
                ->where('warehouse_id', $data->warehouse_id)
                ->sum('quantity');

            $recordsArray[] = [
                'warehouse' => $data->warehouse->name,
                'item_name' => $data->item->name,
                'item_code' => $data->item->item_code,
                'brand_name' => $data->item->brand->name ?? '',
                'category_name' => $data->item->category->name,
                'purchase_price' => $this->formatWithPrecision($data->item->purchase_price, comma: false),
                'sale_price' => $this->formatWithPrecision($data->item->sale_price, comma: false),
                'quantity' => $this->formatWithPrecision($currentStock, comma: false),
                'unit_name' => $this->itemService->getQuantityInUnit($currentStock, itemId: $data->item_id),
                'stock_impact_color' => ($currentStock <= 0) ? 'danger' : '',
                'stock_value_cost' => $this->formatWithPrecision($data->item->purchase_price * $currentStock, comma: false),
                'stock_value_sale' => $this->formatWithPrecision($data->item->sale_price * $currentStock, comma: false),
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Records are retrieved!!',
            'data' => $recordsArray,
        ]);
        // } catch (\Exception $e) {
        //         return response()->json([
        //             'status' => false,
        //             'message' => $e->getMessage(),
        //         ], 409);

        // }
    }
}
