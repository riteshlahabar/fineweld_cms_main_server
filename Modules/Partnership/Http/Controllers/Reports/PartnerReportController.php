<?php

namespace Modules\Partnership\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Partnership\Http\Models\ContractItem;
use Modules\Partnership\Http\Models\PartnerSettlement;

class PartnerReportController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    public function getCurrentActiveItemsOfPartner(Request $request)
    {

        $partnerId = $request->input('partner_id');
        $itemId = $request->input('item_id');

        $latestSub = "(
            SELECT ci.partner_id, ci.item_id, MAX(CONCAT(c.contract_date, LPAD(c.id, 10, '0'))) AS latest_key
            FROM contract_items ci
            JOIN contracts c ON c.id = ci.contract_id
            where ci.partner_id = ".($partnerId ?? 'ci.partner_id').'
            '.($itemId ? ' and ci.item_id = '.$itemId : '').'

            GROUP BY ci.partner_id, ci.item_id
        ) latest';

        $preparedData = ContractItem::query()
            ->join('contracts', 'contracts.id', '=', 'contract_items.contract_id')
            ->join(DB::raw($latestSub), function ($join) {
                $join->on('latest.partner_id', '=', 'contract_items.partner_id')
                    ->on('latest.item_id', '=', 'contract_items.item_id')
                    ->on(DB::raw('latest.latest_key'), '=', DB::raw('CONCAT(contracts.contract_date, LPAD(contracts.id, 10, "0"))'));
            })
            ->when($partnerId, function ($query) use ($partnerId) {
                return $query->where('contract_items.partner_id', $partnerId);
            })
            ->when($itemId, function ($query) use ($itemId) {
                return $query->where('contract_items.item_id', $itemId);
            })
            ->select('contract_items.*', 'contracts.contract_date', 'contracts.id as contract_id')
            ->orderBy('contracts.contract_date', 'desc')
            ->orderBy('contracts.id', 'desc')
            ->get();

        return $preparedData;
    }

    /**
     * Item Sale Report
     * */
    public function getPartnerItems(Request $request): JsonResponse
    {
        try {

            $partnerId = $request->input('partner_id');
            $itemId = $request->input('item_id');
            $showActive = $request->boolean('show_active', false);

            if ($showActive) {
                $preparedData = $this->getCurrentActiveItemsOfPartner($request);

                $activeItems = $preparedData->pluck('id')->toArray();
            } else {

                $preparedData = ContractItem::when($partnerId, function ($query) use ($partnerId) {
                    return $query->where('partner_id', $partnerId);
                })
                    ->when($itemId, function ($query) use ($itemId) {
                        return $query->where('item_id', $itemId);
                    })
                    ->get();

                $activeItems = $this->getCurrentActiveItemsOfPartner($request);

                $activeItems = $activeItems->pluck('id')->toArray();
            }

            if ($preparedData->count() == 0) {
                throw new \Exception('No Records Found!!');
            }
            $recordsArray = [];

            foreach ($preparedData as $transaction) {
                $recordsArray[] = [
                    'contract_date' => $this->toUserDateFormat($transaction->contract_date),
                    'contract_code' => $transaction->contract->contract_code,
                    'item_name' => $transaction->item->name,
                    'brand_name' => $transaction->item->brand->name ?? '',
                    'share_type' => ucfirst($transaction->share_type),
                    'share_value' => $transaction->share_value,
                    'partner_name' => $transaction->partner->getFullName(),
                    'status' => in_array($transaction->id, $activeItems) ? 'Active' : '',

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
     *
     * Partner Settlement Report
     *
     */
    public function getPartnerSettlement(Request $request): JsonResponse
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

            $preparedData = PartnerSettlement::when($partnerId, function ($query) use ($partnerId) {
                    return $query->where('partner_id', $partnerId);
                })
                ->whereBetween('settlement_date', [$fromDate, $toDate])
                ->with('partner')
                ->get();

            if ($preparedData->count() == 0) {
                throw new \Exception('No Records Found!!');
            }

            $recordsArray = [];
            $total =  0;

            foreach ($preparedData as $data) {
                if($data->payment_direction == 'paid'){
                    $total -= $data->amount;
                } else {
                    $total += $data->amount;
                }
                $recordsArray[] = [
                    'settlement_date' => $this->toUserDateFormat($data->settlement_date),
                    'partner_name' => $data->partner->getFullName(),
                    'payment_type' => $data->paymentType->name,
                    'payment_direction' => ucfirst($data->payment_direction),
                    'amount' => $this->formatWithPrecision($data->amount, comma: false),
                    'reference_no' => $data->reference_no ?? '',
                    'settlement_code' => $data->settlement_code,
                    'color' => $data->payment_direction == 'paid' ? 'text-danger' : '',
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Records are retrieved!!',
                'data' => $recordsArray,
                'total' => $this->formatWithPrecision($total, comma: false),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);

        }
    }
}
