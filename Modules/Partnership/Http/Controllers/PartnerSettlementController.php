<?php

namespace Modules\Partnership\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Party\Party;
use App\Models\Party\PartyPayment;
use App\Models\Purchase\Purchase;
use App\Models\Sale\Sale;
use App\Services\PartyService;
use App\Services\PaymentTransactionService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Partnership\Http\Models\Partner;
use Modules\Partnership\Http\Models\PartnerSettlement;
use Modules\Partnership\Http\Requests\PartnerSettlementRequest;
use Mpdf\Mpdf;
use Yajra\DataTables\Facades\DataTables;

class PartnerSettlementController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    public function create(): View
    {
        $prefix = PartnerSettlement::latest()->first();
        $lastCountId = $this->getLastCountId();
        $data = [
            'prefix_code' => $prefix->prefix_code ?? '',
            'count_id' => ($lastCountId + 1),
        ];

        return view('partnership::settlement.create', compact('data'));
    }

    /**
     * Get last count ID
     * */
    public function getLastCountId()
    {
        return PartnerSettlement::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }

    /**
     * Edit a Contract Order.
     *
     * @param  int  $id  The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function edit($id): View
    {
        $settlement = PartnerSettlement::findOrFail($id);

        $settlement->operation = 'update';

        return view('partnership::settlement.edit', compact('settlement'));
    }

    public function store(PartnerSettlementRequest $request)
    {

        try {
            DB::beginTransaction();

            $validatedData = $request->validated();


            $partnerId = $request->input('partner_id');
            $transactionDate = $request->input('settlement_date');
            $referenceNo = $request->input('reference_no');
            $paymentTypeId = $request->input('payment_type_id');
            $amount = $request->input('amount');
            $note = $request->input('note');
            $paymentDirection = $request->input('payment_direction');

            /**
             * Get partner details
             * */


            $data = [
                    'prefix_code' => $request->input('prefix_code'),
                    'count_id' => $request->input('count_id'),
                    'settlement_code' => $validatedData['settlement_code'],
                    'settlement_date' => $this->toSystemDateFormat($transactionDate),
                    'payment_type_id' => $paymentTypeId,
                    'amount' => $amount,
                    'note' => $note,
                    'reference_no' => $referenceNo ?? null,
                    'partner_id' => $partnerId,
                    'payment_direction' => $paymentDirection,
            ];
            /**
             * Record Payment Entry in partnerSettlement model
             * */
            if ($request->operation == 'save') {
                $partnerSettlement = PartnerSettlement::create($data);
            }else{
                $partnerSettlement = PartnerSettlement::findOrFail($validatedData['settlement_id']);
                $partnerSettlement->update($data);
            }

            if (! $partnerSettlement) {
                throw new \Exception(__('payment.failed_to_record_payment_transactions'));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_saved_successfully'),
                'id' => $partnerSettlement->id,
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);

        }

    }

    public function delete(Request $request): JsonResponse
    {

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = PartnerSettlement::find($recordId);
            if (! $record) {
                // Invalid record ID, handle the error (e.g., show a message, log, etc.)
                return response()->json([
                    'status' => false,
                    'message' => __('app.invalid_record_id', ['record_id' => $recordId]),
                ]);

            }
            // You can perform additional validation checks here if needed before deletion
        }

        /**
         * All selected record IDs are valid, proceed with the deletion
         * Delete all records with the selected IDs in one query
         * */
        try {
            // Attempt deletion (as in previous responses)

            PartnerSettlement::whereIn('id', $selectedRecordIds)->delete();

            return response()->json([
                'status' => true,
                'message' => __('app.record_deleted_successfully'),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {

            return response()->json([
                'status' => false,
                'message' => __('app.cannot_delete_records'),
            ], 409);

        }
    }

    /**
     * Datatabale
     * */
    public function datatableList(Request $request)
    {

        $data = PartnerSettlement::when($request->user_id, function ($query) use ($request) {
            return $query->where('created_by', $request->user_id);
        })
            ->when($request->from_date, function ($query) use ($request) {
                return $query->where('settlement_date', '>=', $this->toSystemDateFormat($request->from_date));
            })
            ->when($request->to_date, function ($query) use ($request) {
                return $query->where('settlement_date', '<=', $this->toSystemDateFormat($request->to_date));
            })
            ->when($request->partner_id, function ($query) use ($request) {
                return $query->where('partner_id', $request->partner_id);
            });

        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchTerm = $request->search['value'];
                    $query->where(function ($q) use ($searchTerm) {
                        $q->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('username', 'like', "%{$searchTerm}%");
                        });
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('username', function ($row) {
                return $row->user->username ?? '';
            })
            ->addColumn('partner_name', function ($row) {
                return $row->partner->name;
            })
            ->addColumn('payment_type', function ($row) {
                return $row->paymentType->name;
            })
            ->addColumn('amount', function ($row) {
                // Return the formatted balance
                return $this->formatWithPrecision($row->amount, comma: true);
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;
                $editUrl = route('partnership.partner.settlement.edit', ['id' => $id]);
                $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="'.$editUrl.'"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> '.__('app.edit').'</a>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'><i class="bx bx-trash"></i> '.__('app.delete').'</button>
                                </li>
                            </ul>
                        </div>';

                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
