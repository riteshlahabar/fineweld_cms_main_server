<?php

namespace App\Models;

use App\Models\Items\ItemTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Tax extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'rate',
        'status',
    ];

    /**
     * Insert & update User Id's
     * */
    protected static function boot()
    {
        parent::boot();

        /**
         * creating
         * updating
         * */
        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });

        /**
         * created
         * updated
         * cache created in App\Services\CacheService.php
         * */
        static::created(function ($tax) {
            Cache::forget('tax');
        });
        static::updated(function ($tax) {
            Cache::forget('tax');
        });
        static::deleted(function ($tax) {
            Cache::forget('tax');
        });
    }

    public function service(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /*public function itemTransaction() : HasMany
    {
        return $this->hasMany(ItemTransactione::class);
    }*/

    public function orderedProducts(): HasMany
    {
        return $this->hasMany(OrderedProduct::class, 'tax_id');
    }

    /**
     * Define the relationship between Order and User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
