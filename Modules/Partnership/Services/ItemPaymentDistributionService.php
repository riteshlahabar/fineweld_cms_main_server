<?php

namespace Modules\Partnership\Services;

use App\Models\Sale\Sale;
use App\Models\Sale\SaleReturn;
use Modules\Partnership\Http\Models\ItemProfit;
use Modules\Partnership\Http\Models\Partner;

class ItemPaymentDistributionService
{
    public $transactionOf = null;

    public function distributeSaleReturnPayment($returnId)
    {
        $this->transactionOf = 'sale_return';

        $return = SaleReturn::find($returnId);
        $returnItems = $return->itemTransaction;

        $return->date = $return->return_date;

        $return->itemProfitTransactions = ItemProfit::where('sale_return_id', $return->id)->get();

        $this->distributePayment($return, $returnItems);
    }

    /**
     * Distribute sale payment among partners based on sale items
     */
    public function distributeSalePayment($saleId)
    {
        $this->transactionOf = 'sale';

        $sale = Sale::find($saleId);
        $saleItems = $sale->itemTransaction;

        $sale->itemProfitTransactions = ItemProfit::where('sale_id', $sale->id)->get();

        $this->distributePayment($sale, $saleItems);
    }

    public function distributePayment($model, $modelItems)
    {

        // Is Payment Transaction Exist for Sale
        $paymentTransactions = $model->paymentTransaction;

        // Calculate total sale payment
        $totalPaidAmount = $paymentTransactions->sum('amount');

        // Is Items Exist in Sale
        if ($modelItems->isEmpty()) {
            return;
        }

        // Calculate total sale amount for proportional distribution
        $totalSaleAmount = $modelItems->sum('total');
        if ($totalSaleAmount <= 0) {
            return;
        }

        // Get the default partner
        $defaultPartnerId = Partner::where('default_partner', 1)->first()?->id;
        if (is_null($defaultPartnerId)) {
            throw new \Exception('Default partner is not set. Please set a default partner to proceed.');
        }

        // Get Sale Profit
        if ($model->itemProfitTransactions->isEmpty()) {
            return;
        }

        // Get item IDs
        $itemsIds = $model->itemProfitTransactions->pluck('item_id')->unique()->toArray();
        if (count($itemsIds) == 0) {
            return;
        }

        foreach ($model->itemProfitTransactions as $ItemProfit) {

            // allocate payment to this item proportionally to its total
            $paymentForItem = ($ItemProfit->total / $totalSaleAmount) * $totalPaidAmount;

            // Update ItemProfit Model with the allocated payment for this item
            if ($this->transactionOf == 'sale_return') {
                $ItemProfit->paid_amount = $paymentForItem;
            } else {
                $ItemProfit->received_amount = $paymentForItem;
            }
            $ItemProfit->save();

            // Distribute payment proportionally to each partner for this item
            foreach ($ItemProfit->profitTransaction as $partnerProfitShare) {
                // partner share of the payment allocated to this item
                $distributedAmount = $paymentForItem * ($partnerProfitShare->share_value / 100.0);

                // update paid amount in sale profit for this partner
                if ($this->transactionOf == 'sale_return') {
                    $partnerProfitShare->distributed_paid_amount = $distributedAmount;
                } else {
                    $partnerProfitShare->distributed_received_amount = $distributedAmount;
                }
                $partnerProfitShare->save();
            }

        }

    }
}
