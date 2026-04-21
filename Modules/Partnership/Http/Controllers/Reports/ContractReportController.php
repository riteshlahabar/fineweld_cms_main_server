<?php

namespace Modules\Partnership\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Partnership\Http\Models\Contract;

class ContractReportController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    public function getContracts(Request $request): JsonResponse
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

            $preparedData = Contract::whereBetween('contract_date', [$fromDate, $toDate])->get();

            if ($preparedData->count() == 0) {
                throw new \Exception('No Records Found!!');
            }

            $recordsArray = [];

            foreach ($preparedData as $data) {
                $recordsArray[] = [
                    'contract_date' => $this->toUserDateFormat($data->contract_date),
                    'contract_code' => $data->contract_code,
                    'reference_no' => $data->reference_no ?? '',
                    'total_items' => $data->contractItems()->count(),
                    'total_partners' => $data->contractItems()->count(),
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
    public function getContractItems(Request $request): JsonResponse
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

            $preparedData = Contract::with(['contractItems' => function ($q) use ($itemId, $brandId, $partnerId) {
                if ($itemId) {
                    $q->where('item_id', $itemId);
                }
                if ($brandId) {
                    $q->whereHas('item', function ($query) use ($brandId) {
                        $query->where('brand_id', $brandId);
                    });
                }
                if ($partnerId) {
                    $q->where('partner_id', $partnerId);
                }
            }])
                ->whereBetween('contract_date', [$fromDate, $toDate])
                ->get();

            if ($preparedData->count() == 0) {
                throw new \Exception('No Records Found!!');
            }
            $recordsArray = [];

            foreach ($preparedData as $data) {
                foreach ($data->contractItems as $transaction) {
                    $recordsArray[] = [
                        'contract_date' => $this->toUserDateFormat($data->contract_date),
                        'contract_code' => $data->contract_code,
                        'item_name' => $transaction->item->name,
                        'brand_name' => $transaction->item->brand->name ?? '',
                        'share_type' => ucfirst($transaction->share_type),
                        'share_value' => $transaction->share_value,
                        'partner_name' => $transaction->partner->getFullName(),

                    ];

                }

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
}
