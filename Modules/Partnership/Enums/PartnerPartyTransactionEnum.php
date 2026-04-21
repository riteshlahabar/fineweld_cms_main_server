<?php

namespace Modules\Partnership\Enums;

enum PartnerPartyTransactionEnum: string
{
    case TRANSACTION_TYPE_TO_PAY = 'TO_PAY';
    case TRANSACTION_TYPE_TO_RECEIVE = 'TO_RECEIVE';
    case TRANSACTION_TYPE_PAID = 'PAID';
    case TRANSACTION_TYPE_RECEIVED = 'RECEIVED';
}
