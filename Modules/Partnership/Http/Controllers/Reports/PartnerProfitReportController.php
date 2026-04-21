<?php

namespace Modules\Partnership\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Partnership\Http\Models\PartnerProfitShare;

class PartnerProfitReportController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    public function getPartnerProfit(Request $request): JsonResponse
    {
        try {
            // Validation rules
            $rules = [
                'from_date' => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
                'to_date' => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $fromDate = $request->input('from_date');
            $fromDate = $this->toSystemDateFormat($fromDate);
            $toDate = $request->input('to_date');
            $toDate = $this->toSystemDateFormat($toDate);
            $partnerId = $request->input('partner_id');

            $preparedData = PartnerProfitShare::select('partner_id', DB::raw('SUM(distributed_profit_amount) as total_distributed_profit'))
                ->when($partnerId, function ($query) use ($partnerId) {
                    return $query->where('partner_id', $partnerId);
                })
                ->whereBetween('transaction_date', [$fromDate, $toDate])
                ->groupBy('partner_id')
                ->with('partner')
                ->get();

            if ($preparedData->count() == 0) {
                throw new \Exception('No Records Found!!');
            }

            $recordsArray = [];

            foreach ($preparedData as $data) {
                $recordsArray[] = [
                    'partner_name' => $data->partner->getFullName(),
                    'total_distributed_profit' => $this->formatWithPrecision($data->total_distributed_profit, comma: false),
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
     * Item Sale Report
     * */
    public function getPartnerProfitItemWise(Request $request): JsonResponse
    {
         try {
            // Validation rules
            $rules = [
                'from_date' => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
                'to_date' => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $fromDate = $request->input('from_date');
            $fromDate = $this->toSystemDateFormat($fromDate);
            $toDate = $request->input('to_date');
            $toDate = $this->toSystemDateFormat($toDate);
            $partnerId = $request->input('partner_id');
            $itemId = $request->input('item_id');
            $brandId = $request->input('brand_id');
            $showDetailed = $request->boolean('show_detailed', false);

            if ($showDetailed) {
                $preparedData = PartnerProfitShare::when($partnerId, function ($query) use ($partnerId) {
                    return $query->where('partner_id', $partnerId);
                })
                    ->when($itemId, function ($query) use ($itemId) {
                        return $query->where('item_id', $itemId);
                    })
                    ->when($brandId, function ($query) use ($brandId) {
                        $query->whereHas('item', function ($q) use ($brandId) {
                            $q->where('brand_id', $brandId);
                        });
                    })
                    ->whereBetween('transaction_date', [$fromDate, $toDate])
                    ->with(['partner', 'item.brand', 'profitTransaction.sale', 'profitTransaction.saleReturn'])
                    ->get();

                if ($preparedData->count() == 0) {
                    throw new \Exception('No Records Found!!');
                }
                $recordsArray = [];

                foreach ($preparedData as $data) {
                    // dd($data->profitTransaction);
                    $recordsArray[] = [
                        'transaction_date' => $this->toUserDateFormat($data->transaction_date),
                        'sale_or_return_code' => $data->sale_id ? $data->profitTransaction->sale->sale_code : ($data->sale_return_id ? $data->profitTransaction->saleReturn->return_code : ''),
                        'transaction_type' => $data->sale_id ? 'Sale' : ($data->sale_return_id ? 'Sale Return' : ''),
                        'party_name' => $data->sale_id ? $data->profitTransaction->sale->party->getFullName() : ($data->sale_return_id ? $data->profitTransaction->saleReturn->party->getFullName() : ''),
                        'item_name' => $data->item->name,
                        'brand_name' => $data->item->brand->name ?? '',
                        'partner_name' => $data->partner->getFullName(),
                        'share_type' => ucfirst($data->share_type),
                        'share_value' => $data->share_value,
                        'total_distributed_profit' => $this->formatWithPrecision($data->distributed_profit_amount, comma: false),
                        'distributed_received_amount' => $this->formatWithPrecision($data->distributed_received_amount, comma: false),
                        'distributed_paid_amount' => $this->formatWithPrecision($data->distributed_paid_amount, comma: false),
                    ];

                }

            } else {
                $preparedData = PartnerProfitShare::select(
                    'partner_id',
                    'item_id',
                    DB::raw('SUM(distributed_profit_amount) as total_distributed_profit')
                )
                    ->when($partnerId, function ($query) use ($partnerId) {
                        $query->where('partner_id', $partnerId);
                    })
                    ->when($itemId, function ($query) use ($itemId) {
                        $query->where('item_id', $itemId);
                    })
                    ->when($brandId, function ($query) use ($brandId) {
                        $query->whereHas('item', function ($q) use ($brandId) {
                            $q->where('brand_id', $brandId);
                        });
                    })
                    ->whereBetween('transaction_date', [$fromDate, $toDate])
                    ->groupBy('partner_id', 'item_id')
                    ->with(['partner', 'item.brand'])
                    ->get();

                if ($preparedData->count() == 0) {
                    throw new \Exception('No Records Found!!');
                }
                $recordsArray = [];

                foreach ($preparedData as $data) {
                    $recordsArray[] = [
                        'item_name' => $data->item->name,
                        'brand_name' => $data->item->brand->name ?? '',
                        'partner_name' => $data->partner->getFullName(),
                        'total_distributed_profit' => $this->formatWithPrecision($data->total_distributed_profit, comma: false),
                    ];

                }
            }// else

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
}
