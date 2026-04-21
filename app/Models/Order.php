<?php

namespace App\Models;

use App\Models\Party\Party;
use App\Traits\FormatsDateInputs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Import your trait

class Order extends Model
{
    use FormatsDateInputs;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'party_id',
        'order_date',
        'prefix_code',
        'count_id',
        'order_code',
        'order_status',
        'total_amount',
        'note',
        'status',
    ];

    /**
     * This method calling the Trait FormatsDateInputs
     *
     * @return null or string
     * */
    public function getFormattedOrderDateAttribute()
    {
        return $this->toUserDateFormat($this->order_date); // Call the trait method
    }

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
     * Relationship with Services
     */
    public function orderedProducts(): HasMany
    {
        return $this->hasMany(OrderedProduct::class);
    }

    /**
     * Relationship with Order Payments
     */
    public function payment(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    /**
     * Get the tax associated with the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Define the relationship between Order and User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
