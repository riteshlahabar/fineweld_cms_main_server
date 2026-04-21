<?php

namespace Modules\Partnership\Http\Models;

use App\Models\PaymentTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PartnerPartyTransaction extends Model
{
    protected $fillable = [
        'transaction_date',
        'amount',
        'note',
        'payment_transaction_id',
        'partner_id',
        'unique_code',
        'description',
        'payment_type_id',
        'party_transaction_id',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Get Payment Type
     * */
    public function paymentType(): HasOne
    {
        return $this->hasOne(PaymentTypes::class, 'id', 'payment_type_id');
    }
}
