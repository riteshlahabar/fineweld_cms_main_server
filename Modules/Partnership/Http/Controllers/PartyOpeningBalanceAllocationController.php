<?php

namespace Modules\Partnership\Http\Controllers;

use App\Enums\General;
use App\Http\Controllers\Controller;
use App\Models\Party\PartyPayment;
use App\Models\Party\PartyTransaction;
use App\Models\PaymentTransaction;
use App\Models\Sale\Sale;
use App\Services\AccountTransactionService;
use App\Services\PartyService;
use App\Services\PaymentTransactionService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Partnership\Services\PartnerPartyTransactionService;
use Mpdf\Mpdf;
use Yajra\DataTables\Facades\DataTables;

class PartyOpeningBalanceAllocationController extends Controller
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
        $data = PartyTransaction::when($request->user_id, fn ($q) => $q->where('created_by', $request->user_id))
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
                        // ->orWhereHas('paymentType', fn($q) => $q->where('name', 'like', "%{$searchTerm}%"))
                        // ->orWhere('reference_no', 'like', "%{$searchTerm}%")
                            ->orWhere('to_pay', 'like', "%{$searchTerm}%")
                            ->orWhere('to_receive', 'like', "%{$searchTerm}%");
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

            ->addColumn('party_name', function ($row) {
                return $row->party->getFullName();
            })
            ->addColumn('mobile', function ($row) {
                return $row->party->mobile;
            })
            ->addColumn('party_type', function ($row) {
                return ucfirst($row->party->party_type);
            })
            ->editColumn('payment_direction', function ($row) {
                return match (true) {
                    $row->to_pay > 0 => ['text' => 'To Pay', 'color' => 'danger'],
                    $row->to_receive > 0 => ['text' => 'To Receive', 'color' => 'success'],
                    default => ['text' => 'No Balance', 'color' => 'secondary'],
                };
            })
            ->editColumn('amount', function ($row) {
                return $this->formatWithPrecision($row->to_pay + $row->to_receive);
            })
            ->addColumn('allocated_amount', function ($row) {
                $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedPartyBalanceAmount($row->id);

                return $this->formatWithPrecision($existingAllocationsSum);
            })

            ->addColumn('balance', function ($row) {
                $balanceAfterAdjustmentAmount = $row->to_pay + $row->to_receive;
                $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedPartyBalanceAmount($row->id);

                return $this->formatWithPrecision($balanceAfterAdjustmentAmount - $existingAllocationsSum);
            })
            ->addColumn('balance_color', function ($row) {
                $balanceAfterAdjustmentAmount = $row->to_pay + $row->to_receive;
                $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedPartyBalanceAmount($row->id);

                return (($balanceAfterAdjustmentAmount - $existingAllocationsSum) > 0) ? 'text-danger fw-bold' : '';
            })
            ->addColumn('username', function ($row) {
                return $row->party->user->username ?? '';
            })
            ->addColumn('action', function ($row) {
                $actionBtn = '<button type="button" class="btn btn-sm btn-outline-primary partner-party-balance-allocation" data-id="'.$row->id.'"><i class="bx bx-transfer "></i> '.__('app.allocation').'</button>';

                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function showPartnerPartyBalanceAllocationModal(int $paymentTransactionId)
    {

        $partyTransaction = PartyTransaction::find($paymentTransactionId);

        $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedPartyBalanceAmount($partyTransaction->id);

        $partyTransaction->amount = $partyTransaction->to_pay > 0 ? $partyTransaction->to_pay : $partyTransaction->to_receive;

        $partyTransaction->remaining_amount = $partyTransaction->amount - $existingAllocationsSum;

        $partyTransaction->partnerPartyTransactions = $this->partnerPartyTransactionService->getAllocationsForPartyBalance($partyTransaction->id);

        // Return rendered HTML, not JSON
        $html = view('partnership::modals.parter-party-balance-allocation', ['partyTransaction' => $partyTransaction])->render();

        return response()->json(['html' => $html]);

    }

    public function partnerPartyBalanceAllocationStore(Request $request)
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

            $partyTransaction = PartyTransaction::find($request->input('party_transaction_id'));
            $partyTransaction->amount = $partyTransaction->to_pay > 0 ? $partyTransaction->to_pay : $partyTransaction->to_receive;

            // validate is already has the allocation for the amount
            $existingAllocationsSum = $this->partnerPartyTransactionService->getSumOfAllocatedPartyBalanceAmount($partyTransaction->id);

            if ($partyTransaction->amount < ($existingAllocationsSum + $request->input('amount'))) {
                // if he manually typed amount more than remaining amount
                throw new \Exception(__('partnership::partner.allocation_amount_exceeds_payment_amount'));
            }

            $record = $this->partnerPartyTransactionService->recordPartnerTransactionEntry(
                $partyTransaction,
                [
                    'transaction_date' => $request->input('transaction_date'),
                    'amount' => $request->input('amount'),
                    'note' => $request->input('note'),
                    'payment_type_id' => $request->input('payment_type_id'),
                    'partner_id' => $request->input('partner_id'),
                    'simple_unique_code' => $partyTransaction->to_pay > 0 ? 'pay' : 'receive',
                    'party_transaction_id' => $partyTransaction->id,
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
