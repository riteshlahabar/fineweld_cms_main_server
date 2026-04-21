<?php

namespace Modules\Partnership\Services;

use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Modules\Partnership\Http\Models\Partner;

class PartnerTransactionService
{
    use FormatNumber;
    use FormatsDateInputs;

    /**
     * Record Item Transactions
     *
     * */
    public function recordPartnerTransactionEntry(Partner $partnerModel, array $data)
    {
        // return true;
        $modelId = $partnerModel->id;

        $transaction = $partnerModel->transaction()->create(
            [
                'transaction_date' => $this->toSystemDateFormat($data['transaction_date']),
                'partner_id' => $partnerModel->id,
                'to_pay' => $data['to_pay'],
                'to_receive' => $data['to_receive'],
            ]
        );

        return $transaction;
    }
}
