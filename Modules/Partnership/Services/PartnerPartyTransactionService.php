<?php

namespace Modules\Partnership\Services;

use App\Models\Party\PartyPayment;
use App\Models\Party\PartyTransaction;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Support\Facades\DB;
use Modules\Partnership\Enums\PartnerPartyTransactionEnum;
use Modules\Partnership\Http\Models\PartnerPartyTransaction;

class PartnerPartyTransactionService
{
    use FormatNumber;
    use FormatsDateInputs;

    /**
     * Record Item Transactions
     *
     * */
    public function recordPartnerTransactionEntry(PartyPayment|PartyTransaction $model, array $data)
    {
        $uniqueCode = match ($data['simple_unique_code']) {
            'pay' => PartnerPartyTransactionEnum::TRANSACTION_TYPE_TO_PAY->value,// Used in PartyPayment
            'receive' => PartnerPartyTransactionEnum::TRANSACTION_TYPE_TO_RECEIVE->value,// Used in PartyPayment
            'paid' => PartnerPartyTransactionEnum::TRANSACTION_TYPE_PAID->value,
            'received' => PartnerPartyTransactionEnum::TRANSACTION_TYPE_RECEIVED->value,
            default => null,
        };
        if (is_null($uniqueCode)) {
            throw new \Exception('Invalid unique code provided.');
        }

        $transaction = PartnerPartyTransaction::create(
            [
                'transaction_date' => $this->toSystemDateFormat($data['transaction_date']),
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'payment_type_id' => $model->payment_type_id ?? null,
                'partner_id' => $data['partner_id'],
                'unique_code' => $uniqueCode,
                'payment_transaction_id' => $data['payment_transaction_id'] ?? null,
                'party_transaction_id' => $data['party_transaction_id'] ?? null,
            ]
        );

        return $transaction;
    }

    public function getSumOfAllocatedPartyBalanceAmount($partyTransactionId)
    {
        return PartnerPartyTransaction::where('party_transaction_id', $partyTransactionId)->sum('amount');
    }

    public function getSumOfAllocatedAmount($paymentTransactionId)
    {
        return PartnerPartyTransaction::where('payment_transaction_id', $paymentTransactionId)->sum('amount');
    }

    public function getAllocationsForPartyPayment($paymentTransactionId)
    {
        return PartnerPartyTransaction::where('payment_transaction_id', $paymentTransactionId)->get();
    }

    public function getAllocationsForPartyBalance($partyTransactionId)
    {
        return PartnerPartyTransaction::where('party_transaction_id', $partyTransactionId)->get();
    }

    public function deletePartnerPartyTransaction($partnerPartyTransactionId)
    {
        $partnerPartyTransaction = PartnerPartyTransaction::findOrFail($partnerPartyTransactionId);

        return $partnerPartyTransaction->delete();
    }

    public function getSumOfPartnerPartyTransactionByPartnerId($partnerIds)
    {
        $totals = PartnerPartyTransaction::select('unique_code', DB::raw('SUM(amount) as total'), 'partner_id')
            ->whereIn('partner_id', $partnerIds)
            ->whereIn('unique_code', [
                PartnerPartyTransactionEnum::TRANSACTION_TYPE_TO_PAY->value,
                PartnerPartyTransactionEnum::TRANSACTION_TYPE_TO_RECEIVE->value,
                PartnerPartyTransactionEnum::TRANSACTION_TYPE_PAID->value,
                PartnerPartyTransactionEnum::TRANSACTION_TYPE_RECEIVED->value,
            ])
            ->groupBy('unique_code')
            ->pluck('total', 'unique_code', 'partner_id');
    }
}
