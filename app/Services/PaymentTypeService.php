<?php

namespace App\Services;

use App\Enums\PaymentTypesUniqueCode;
use App\Models\PaymentTypes;

class PaymentTypeService
{
    /**
     * Get first default Payment Type for auto selection while making invoice.
     * */
    public function selectedPaymentTypesArray($default = true): array
    {
        if ($default) {
            $paymentType = PaymentTypes::where('unique_code', PaymentTypesUniqueCode::CASH->value)->select('id', 'name')->first();

            return $paymentType ? $paymentType->toArray() : [];
        } else {
            return [];
        }
    }

    public function returnPaymentTypeId($paymentTypeUniqueName)
    {
        $paymentType = PaymentTypes::where('unique_code', $paymentTypeUniqueName)->select('id', 'name')->first();

        return $paymentType ? $paymentType->id : null;
    }
}
