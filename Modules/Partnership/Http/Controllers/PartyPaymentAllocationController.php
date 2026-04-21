<?php

namespace Modules\Partnership\Http\Controllers;

use App\Enums\General;
use App\Http\Controllers\Controller;
use App\Models\Party\PartyPayment;
use App\Models\PaymentTransaction;
use App\Services\AccountTransactionService;
use App\Services\PartyService;
use App\Services\PaymentTransactionService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Partnership\Services\PartnerPartyTransactionService;
use Yajra\DataTables\Facades\DataTables;

class PartyPaymentAllocationController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    private $paymentTransactionService;

    private $accountTransactionService;

    private $partyService;

    private $partnerPartyTransactionService;

    public function __construct(
        PaymentTransactionService $paymentTransactionService,
        AccountTransactionService $accountTransactionService,
        PartyService $partyService,
        PartnerPartyTransactionService $partnerPartyTransactionService
    ) {
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->partyService = $partyService;
        $this->partnerPartyTransactionService = $partnerPartyTransactionService;
    }

    /**
     * Datatabale
     * */
    public function datatableList(Request $request)
    {
        $data = PartyPayment::with('paymentTransaction')->whereHas('paymentTransaction', function ($query) {
            $query->where('payment_from_unique_code', 'PARTY_BALANCE_AFTER_ADJUSTMENT');
        })
                        // ->when($request->party_id, fn($q) => $q->where('party_id', $request->party_id))
            ->when($request->user_id, fn ($q) => $q->where('created_by', $request->user_id))
            ->when($request->from_date, fn ($q) => $q->where('transaction_date', '>=', $this->toSystemDateFormat($request->from_date)))
            ->when($request->to_date, fn ($q) => $q->where('transaction_date', '<=', $this->toSystemDateFormat($request->to_date)));

        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                if ($request->filled('search.value')) {
                    $searchTerm = $request->input('search.value');
                    $query->where(function ($q) use ($searchTerm) {
                        $q->whereHas('party', fn ($q) => $q->where('first_name', 'like', "%{$searchTerm}%")
                            ->orWhere('last_name', 'like', "%{$searchTerm}%")
                            ->orWhere('party_type', 'like', "%{$searchTerm}%")
                            ->orWhere('mobile', 'like', "%{$searchTerm}%"))
                            ->orWhereHas('user', fn ($q) => $q->where('username', 'like', "%{$searchTerm}%"))
                            ->orWhereHas('paymentType', fn ($q) => $q->where('name', 'like', "%{$searchTerm}%"))
                            ->orWhere('reference_no', 'like', "%{$searchTerm}%")
                            ->orWhere('amount', 'like', "%{$searchTerm}%");
                    });
                }
            })
            ->addIndexColumn()
            ->editColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->editColumn('transaction_date', function ($row) {
                return $row->formatted_transaction_date;
            })
            ->editColumn('reference_no', function ($row) {
                return $row->reference_no;
            })
            ->addColumn('party_name', function ($row) {
                return $row->party->getFullName();
            })
            ->addColumn('party_type', function ($row) {
                return ucfirst($row->party->party_type);
            })
            ->addColumn('payment_type', function ($row) {
                return $row->paymentType->name;
            })
            ->orderColumn('payment_type', function ($query, $order) {
                $query->join('payment_types', 'party_payments.payment_type_id', '=', 'payment_types.id')
                    ->orderBy('payment_types.name', $order);
            })
            ->editColumn('payment_direction', function ($row) {
                return ($row->payment_direction == 'pay') ? ['text' => 'Paid', 'color' => 'danger'] : ['text' => 'Received', 'color' => 'success'];
            })
            ->editColumn('amount', function ($row) {
                $balanceAfterAdjustmentAmount = $row->paymentTransaction->first()->amount;

                return $this->formatWithPrecision($balanceAfterAdjustmentAmount);
            })
            ->addColumn('allocated_amount', function ($row) {
                // PartnerPartyTransactionService
                $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedAmount($row->paymentTransaction->first()->id);

                // $row->existingAllocationsSum = $existingAllocationsSum;

                return $this->formatWithPrecision($existingAllocationsSum);
            })

            ->addColumn('balance', function ($row) {
                $balanceAfterAdjustmentAmount = $row->paymentTransaction->first()->amount;
                $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedAmount($row->paymentTransaction->first()->id);

                return $this->formatWithPrecision($balanceAfterAdjustmentAmount - $existingAllocationsSum);
            })
            ->addColumn('balance_color', function ($row) {
                $balanceAfterAdjustmentAmount = $row->paymentTransaction->first()->amount;
                $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedAmount($row->paymentTransaction->first()->id);

                return (($balanceAfterAdjustmentAmount - $existingAllocationsSum) > 0) ? 'text-danger fw-bold' : '';
            })
            ->addColumn('username', function ($row) {
                return $row->user->username ?? '';
            })
            ->addColumn('action', function ($row) {
                $actionBtn = '<button type="button" class="btn btn-sm btn-outline-primary partner-party-payment-allocation" data-id="'.$row->paymentTransaction->first()->id.'"><i class="bx bx-transfer "></i> '.__('app.allocation').'</button>';

                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function showPartnerPartyPaymentAllocationModal(int $paymentTransactionId)
    {

        $paymentTransaction = PaymentTransaction::with('transaction')->find($paymentTransactionId);

        $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedAmount($paymentTransaction->id);

        $paymentTransaction->remaining_amount = $paymentTransaction->amount - $existingAllocationsSum;

        $paymentTransaction->partnerPartyTransactions = $this->partnerPartyTransactionService->getAllocationsForPartyPayment($paymentTransaction->id);

        // Return rendered HTML, not JSON
        $html = view('partnership::modals.parter-party-payment-allocation', ['paymentTransaction' => $paymentTransaction])->render();

        return response()->json(['html' => $html]);

    }

    public function partnerPartyPaymentAllocationStore(Request $request)
    {
        try {
            DB::beginTransaction();

            $rules = [
                'transaction_date' => 'required|date_format:'.implode(',', $this->getDateFormats()),
                'amount' => 'required|numeric|gt:0',
                'partner_id' => 'required|integer',
            ];

            $messages = [
                'transaction_date.required' => 'Transaction date is required.',
                'transaction_date.date_format' => 'Invalid transaction date format.',
                'amount.required' => 'Amount is required.',
                'amount.numeric' => 'Amount must be a number.',
                'amount.gt' => 'Amount must be greater than zero.',
                'partner_id.required' => 'Partner name is required.',
                'partner_id.integer' => 'Invalid partner selection.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $paymentTransaction = PaymentTransaction::find($request->input('payment_transaction_id'));

            // validate is already has the allocation for the amount
            $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedAmount($paymentTransaction->id);

            if ($paymentTransaction->amount < ($existingAllocationsSum + $request->input('amount'))) {
                // if he manually typed amount more than remaining amount
                throw new \Exception(__('partnership::partner.allocation_amount_exceeds_payment_amount'));
            }

            $partyPayment = PartyPayment::find($paymentTransaction->transaction_id);

            $record = $this->partnerPartyTransactionService->recordPartnerTransactionEntry(
                $partyPayment,
                [
                    'transaction_date' => $request->input('transaction_date'),
                    'amount' => $request->input('amount'),
                    'note' => $request->input('note'),
                    'payment_type_id' => $request->input('payment_type_id'),
                    'partner_id' => $request->input('partner_id'),
                    'simple_unique_code' => ($partyPayment->payment_direction == 'pay') ? 'paid' : 'received',
                    'payment_transaction_id' => $paymentTransaction->id,
                    // 'transaction_type' => '',
                    // 'transaction_id' => null,
                ]
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_saved_successfully'),
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);

        }
    }

    public function deletePartnerPartyTransaction($transactionId)
    {
        try {
            DB::beginTransaction();

            $deletePartnerPartyTransaction = $this->partnerPartyTransactionService->deletePartnerPartyTransaction($transactionId);

            if (! $deletePartnerPartyTransaction) {
                throw new \Exception(__('partnership::partner.failed_to_delete_partner_party_transaction'));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_deleted_successfully'),
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);

        }
    }
}
