<?php

namespace Modules\Partnership\Http\Models;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Partner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prefix_code',
        'count_id',
        'partner_code',
        'partner_type',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'mobile',
        'phone',
        'whatsapp',
        'address',
        'tax_number',
        'tax_type',
        'website',
        'state_id',
        'to_pay',
        'to_receive',
        'currency_id',
        'status',
        // 'default_partner',
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
     * Define the relationship between Party and User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Define the relationship between Item Transaction & Items table.
     * Used to save Opening Balance and other payments
     */
    public function transaction(): MorphMany
    {
        return $this->morphMany(PartnerTransaction::class, 'transaction');
    }

    public function getFullName()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
