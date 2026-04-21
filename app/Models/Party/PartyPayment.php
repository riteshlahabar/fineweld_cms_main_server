<?php

namespace App\Models\Party;

use App\Models\PaymentTransaction;
use App\Models\PaymentTypes;
use App\Models\User;
use App\Traits\FormatsDateInputs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PartyPayment extends Model
{
    use FormatsDateInputs;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_date',
        'party_id',
        'payment_type_id',
        'payment_direction',
        'amount',
        'reference_no',
        'note',
    ];

    /**
     * Insert & update User Id's
     * */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    /**
     * Define the relationship between Order and Party.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    /**
     * Define the relationship between Order and User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * This method calling the Trait FormatsDateInputs
     *
     * @return null or string
     *              Use it as formatted_transaction_date
     * */
    public function getFormattedTransactionDateAttribute()
    {
        return $this->toUserDateFormat($this->transaction_date); // Call the trait method
    }

    /**
     * Get Payment Type
     * */
    public function paymentType(): HasOne
    {
        return $this->hasOne(PaymentTypes::class, 'id', 'payment_type_id');
    }

    public function partyPaymentAllocation(): HasMany
    {
        return $this->hasMany(PartyPaymentAllocation::class);
    }

    /**
     * Define the relationship between Expense Payment Transaction & Expense table.
     */
    public function paymentTransaction(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'transaction');
    }

    public function getTableCode(): void
    {
        // No return value needed for void function
    }
}
