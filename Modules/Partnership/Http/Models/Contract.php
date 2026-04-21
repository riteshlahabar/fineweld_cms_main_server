<?php

namespace Modules\Partnership\Http\Models;

use App\Models\User;
use App\Traits\FormatsDateInputs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use FormatsDateInputs;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'contract_date',
        'prefix_code',
        'count_id',
        'contract_code',
        'reference_no',
        'notes',
        'remarks',
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
     * This method calling the Trait FormatsDateInputs
     *
     * @return null or string
     *              Use it as formatted_contract_date
     * */
    public function getFormattedContractDateAttribute()
    {
        return $this->toUserDateFormat($this->contract_date); // Call the trait method
    }

    /**
     * Get the contract items for the contract.
     */
    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    /**
     * Define the relationship between Order and User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
