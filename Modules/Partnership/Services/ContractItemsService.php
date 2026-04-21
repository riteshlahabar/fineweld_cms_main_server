<?php

namespace Modules\Partnership\Services;

use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Modules\Partnership\Http\Models\Contract;

class ContractItemsService
{
    use FormatNumber;
    use FormatsDateInputs;

    /**
     * Record Contract Transactions
     *
     * */
    public function recordPartnerContractItems(Contract $model, array $data)
    {
        $itemId = $data['item_id'];
        $contractDate = $this->toSystemDateFormat($data['contract_date']);

        $transaction = $model->contractItems()->create(
            [
                'contract_date' => $contractDate,
                'item_id' => $itemId,
                'description' => $data['description'] ?? null,
                'share_type' => $data['share_type'] ?? null,
                'share_value' => $data['share_value'] ?? 0,
                'partner_id' => $data['partner_id'] ?? null,
            ]
        );

        return $transaction;
    }
}
