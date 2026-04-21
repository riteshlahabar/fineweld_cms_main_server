<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Sale\SaleOrder;
use App\Services\AccountTransactionService;
use App\Services\PaymentTransactionService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SaleOrderPaymentController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    private $paymentTransactionService;

    private $accountTransactionService;

    public function __construct(
        PaymentTransactionService $paymentTransactionService,
        AccountTransactionService $accountTransactionService
    ) {
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
    }

    public function deleteSaleOrderPayment($paymentId): JsonResponse
    {
        try {
            DB::beginTransaction();
            $paymentTransaction = PaymentTransaction::find($paymentId);
            if (! $paymentTransaction) {
                throw new \Exception(__('payment.failed_to_delete_payment_transactions'));
            }

            // Sale model id
            $saleId = $paymentTransaction->transaction_id;

            // Find the related account transaction
            $accountTransactions = $paymentTransaction->accountTransaction;
            if ($accountTransactions->isNotEmpty()) {
                foreach ($accountTransactions as $accountTransaction) {
                    $accountId = $accountTransaction->account_id;
                    // Do something with the individual accountTransaction
                    $accountTransaction->delete(); // Or any other operation
                    // Update  account
                    $this->accountTransactionService->calculateAccounts($accountId);
                }
            }

            $paymentTransaction->delete();

            /**
             * Update Sale Model
             * Total Paid Amunt
             * */
            $sale = SaleOrder::find($saleId);
            if (! $this->paymentTransactionService->updateTotalPaidAmountInModel($sale)) {
                throw new \Exception(__('payment.failed_to_update_paid_amount'));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_deleted_successfully'),
                'data' => $this->getSaleOrderPaymentHistoryData($sale->id),
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);

        }
    }

    public function getSaleOrderPaymentHistoryData($id)
    {
        $model = SaleOrder::with('party', 'paymentTransaction.paymentType')->find($id);

        $data = [
            'party_id' => $model->party->id,
            'party_name' => $model->party->company_name.' '.$model,
            'balance' => $this->formatWithPrecision($model->grand_total - $model->paid_amount),
            'invoice_id' => $id,
            'invoice_code' => $model->sale_code,
            'invoice_date' => $this->toUserDateFormat($model->sale_date),
            'balance_amount' => $this->formatWithPrecision($model->grand_total - $model->paid_amount),
            'paid_amount' => $this->formatWithPrecision($model->paid_amount),
            'paid_amount_without_format' => $model->paid_amount,
            'paymentTransactions' => $model->paymentTransaction->map(function ($transaction) {
                return [
                    'payment_id' => $transaction->id,
                    'transaction_date' => $this->toUserDateFormat($transaction->transaction_date),
                    'reference_no' => $transaction->reference_no ?? '',
                    'payment_type' => $transaction->paymentType->name,
                    'amount' => $this->formatWithPrecision($transaction->amount),
                ];
            })->toArray(),
        ];

        return $data;
    }
}
