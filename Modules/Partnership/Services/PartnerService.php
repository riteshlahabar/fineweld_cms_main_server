<?php

namespace Modules\Partnership\Services;

use App\Models\Party\PartyPayment;
use App\Models\Party\PartyTransaction;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleReturn;
use App\Traits\FormatNumber;
use Illuminate\Support\Facades\DB;
use Modules\Partnership\Enums\PartnerPartyTransactionEnum;
use Modules\Partnership\Http\Models\Partner;
use Modules\Partnership\Http\Models\PartnerPartyTransaction;
use Modules\Partnership\Http\Models\PartnerProfitShare;
use Modules\Partnership\Http\Models\PartnerSettlement;
use Modules\Partnership\Http\Models\PartnerTransaction;

class PartnerService
{
    use FormatNumber;

    public function getDefaultPartnerId()
    {
        $defaultPartnerId = Partner::where('default_partner', 1)->first()?->id;
        if (is_null($defaultPartnerId)) {
            throw new \Exception('Default partner is not set. Please set a default partner to proceed.');
        }

        return $defaultPartnerId;
    }

    /**
     * Calculate Default & Other Partner Balance of the Party
     * */
    public function getPartnerPartyWiseBalance($partyId, $partnerId)
    {

        // --- First get Party Transaction total ---
        // Party has Alway one record in the this table
        // Positive if receive else negative for pay
        $partyTransaction = PartyTransaction::where('party_id', $partyId)->first();

        $partyTransactionReceiveOrPay = [
            'to_receive' => $partyTransaction->to_receive, // +
            'to_pay' => $partyTransaction->to_pay, // -
        ];

        $partyTransactionBalance = $partyTransactionReceiveOrPay['to_receive'] - $partyTransactionReceiveOrPay['to_pay'];

        // Now check how much money distributed to partner
        $distributedToPartner = PartnerPartyTransaction::where('party_transaction_id', $partyTransaction->id)
            ->select('unique_code', DB::raw('SUM(amount) as total_amount'))
            ->where('partner_id', $partnerId)
            ->groupBy('unique_code')
            ->get();

        // get distributed amount in other partners
        $distributedToPartnerSum = $distributedToPartner->sum('total_amount');

        // get distributed amount in default partners
        $distributedToPartnerSumBalance = $partyTransactionBalance - $distributedToPartnerSum;

        /*********************************** */
        // Payment Transaction Related Calculation
        $partyPayments = PartyPayment::where('party_id', $partyId)->get();

        // payment direction may pay or receive
        $partyPaymentsTotal = $partyPayments
            ->groupBy('payment_direction')
            ->map(function ($group) {
                return [
                    'payment_direction' => $group->first()->payment_direction,
                    'total_amount' => $group->sum('amount'),
                ];
            })
            ->values();
        $inTotalOfParther = $partyPaymentsTotal->where('payment_direction', 'receive')->sum('total_amount');
        $outTotalOfParther = $partyPaymentsTotal->where('payment_direction', 'pay')->sum('total_amount');
        /*************************************/

        // Check The Sale & Sale Return Payments & balance partner wise
        $saleIds = Sale::where('party_id', $partyId)->pluck('id')->toArray();
        $saleReturnIds = SaleReturn::where('party_id', $partyId)->pluck('id')->toArray();

        $partnerProfitShares = PartnerProfitShare::with('profitTransaction')
            ->where('partner_id', $partnerId)
            ->where(function ($query) use ($saleIds, $saleReturnIds) {
                $query->whereIn('sale_id', $saleIds)
                    ->orWhereIn('sale_return_id', $saleReturnIds);
            })
            ->get();

        $partnerTotal = 0;
        foreach ($partnerProfitShares as $partnerProfitShare) {
            // calculate total / share * 100
            $requiredToPayOrReceive = ($partnerProfitShare->profitTransaction->total * $partnerProfitShare->share_value) / 100;

            $receivedAmount = $partnerProfitShare->distributed_received_amount;
            $paidAmount = $partnerProfitShare->distributed_paid_amount;

            $total = ($requiredToPayOrReceive - $paidAmount) - $receivedAmount;

            $partnerTotal += $total;
        }

        $data = [
            'party_id' => $partyId,

            // default Partner Total
            'partnerSum' => $distributedToPartnerSumBalance + ($outTotalOfParther - $inTotalOfParther) + $partnerTotal,

            'detailed' => [
                'distributedToPartnerSumBalance' => $distributedToPartnerSumBalance, // to_receive - to_pay

                'inTotalOfParther' => $inTotalOfParther, // receive
                'outTotalOfParther' => $outTotalOfParther, // pay
                'partnerTotal' => $partnerTotal,
            ],
        ];

        return $data;
    }

    /**
     * Calculate Balance of the Partner
     * */
    public function getPartnerBalance($partnerIds)
    {
        if (empty($partnerIds)) {
            return ['balance' => 0, 'status' => 'no_balance'];
        }

        // --- Opening Balance ---
        $openingBalance = PartnerTransaction::whereIn('partner_id', $partnerIds)
            ->selectRaw('COALESCE(SUM(to_receive) - SUM(to_pay), 0) as opening_balance')
            ->value('opening_balance') ?? 0;

        // --- Allocation Balances ---
        $allocationTypes = [
            'TO_RECEIVE' => PartnerPartyTransactionEnum::TRANSACTION_TYPE_TO_RECEIVE->value,
            'TO_PAY' => PartnerPartyTransactionEnum::TRANSACTION_TYPE_TO_PAY->value,
            'RECEIVED' => PartnerPartyTransactionEnum::TRANSACTION_TYPE_RECEIVED->value,
            'PAID' => PartnerPartyTransactionEnum::TRANSACTION_TYPE_PAID->value,
        ];

        $allocations = PartnerPartyTransaction::whereIn('partner_id', $partnerIds)
            ->whereIn('unique_code', $allocationTypes)
            ->selectRaw('unique_code, COALESCE(SUM(amount),0) as total_amount')
            ->groupBy('unique_code')
            ->pluck('total_amount', 'unique_code');

        $openingBalanceAllocation_TO_RECEIVE = $allocations[$allocationTypes['TO_RECEIVE']] ?? 0;
        $openingBalanceAllocation_TO_PAY = $allocations[$allocationTypes['TO_PAY']] ?? 0;
        $openingBalanceAllocation_RECEIVED = $allocations[$allocationTypes['RECEIVED']] ?? 0;
        $openingBalanceAllocation_PAID = $allocations[$allocationTypes['PAID']] ?? 0;

        // --- Profit Share Pending ---
        $pendingParnerBalanceFromProfitShare = PartnerProfitShare::whereIn('partner_id', $partnerIds)
            //->selectRaw('COALESCE(SUM(distributed_received_amount) - SUM(distributed_paid_amount), 0) as pending_amount')
            ->selectRaw('COALESCE(SUM(distributed_profit_amount)) as pending_amount')
            ->value('pending_amount') ?? 0;

        // --- Parter Settlement Balance Calculation ---
        $partnerSettlement = PartnerSettlement::whereIn('partner_id', $partnerIds)
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_direction = "received" THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN payment_direction = "paid" THEN amount ELSE 0 END), 0) as balance')
            ->value('balance') ?? 0;

        // --- Final Opening Calculation ---
        $balance = (
            ($openingBalanceAllocation_TO_RECEIVE - $openingBalanceAllocation_RECEIVED)
            - ($openingBalanceAllocation_TO_PAY - $openingBalanceAllocation_PAID)
        )
                    + $pendingParnerBalanceFromProfitShare
                    - $partnerSettlement;

        // --- Determine Status ---
        if ($balance > 0) {
            $response = ['balance' => $balance, 'status' => 'you_collect'];
        } elseif ($balance < 0) {
            $response = ['balance' => abs($balance), 'status' => 'you_pay'];
        } else {
            $response = ['balance' => 0, 'status' => 'no_balance'];
        }

        // --- Return with breakdown ---
        return array_merge($response, [
            'openingBalanceAllocation_TO_RECEIVE' => $this->formatWithPrecision($openingBalanceAllocation_TO_RECEIVE, comma: false),
            'openingBalanceAllocation_TO_PAY' => $this->formatWithPrecision($openingBalanceAllocation_TO_PAY, comma: false),
            'openingBalanceAllocation_RECEIVED' => $this->formatWithPrecision($openingBalanceAllocation_RECEIVED, comma: false),
            'openingBalanceAllocation_PAID' => $this->formatWithPrecision($openingBalanceAllocation_PAID, comma: false),
            'pendingParnerBalanceFromProfitShare' => $this->formatWithPrecision($pendingParnerBalanceFromProfitShare, comma: false),
        ]);
    }
}
