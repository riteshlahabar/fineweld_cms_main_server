<?php

namespace App\Traits;

use Illuminate\Support\Number;

trait FormatNumber
{
    public function formatWithPrecision($number, $comma = true)
{
    // ✅ NULL & INVALID SAFE
    if ($number === null || $number === '' || !is_numeric($number)) {
        $number = 0;
    }

    $formatted = Number::format(
        (float) $number,
        app('company')['number_precision']
    );

    return $comma ? $formatted : str_replace(',', '', $formatted);
}


    public function formatQuantity($number)
    {
        return str_replace(',', '', Number::format($number, app('company')['quantity_precision']));
    }

    public function spell($number)
    {
        return Number::spell($number);
    }
}
