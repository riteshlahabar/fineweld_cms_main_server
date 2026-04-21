<?php

namespace Modules\Partnership\Http\Models;

use App\Models\PaymentTypes;
use App\Models\User;
use App\Traits\FormatsDateInputs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSettlement extends Model
{
    use FormatsDateInputs;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'settlement_date',
        'partner_id',
        'payment_type_id',
        'payment_direction',
        'amount',
        'reference_no',
        'note',
        'prefix_code',
        'count_id',
        'settlement_code',
    ];

    /**
     * Format settlement_date attribute
     */
    public function getFormattedSettlementDateAttribute()
    {
        return $this->toUserDateFormat($this->settlement_date); // Call the trait method
    }

    /**
     * Get the partner that owns the settlement.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the payment type associated with the settlement.
     */
    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentTypes::class);
    }

    /**
     * Define the relationship between Party and User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
