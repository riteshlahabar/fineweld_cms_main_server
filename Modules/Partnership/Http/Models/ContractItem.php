<?php

namespace Modules\Partnership\Http\Models;

use App\Models\Items\Item;
use App\Traits\FormatsDateInputs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItem extends Model
{
    use FormatsDateInputs;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'contract_date',
        'contract_id',
        'item_id',
        'description',
        'share_type',
        'share_value',
        'partner_id',
    ];

    /**
     * return boolean
     * if contract_date is null or contract_date >= current date
     */
    public function getIsActiveAttribute()
    {
        return is_null($this->contract_date) || $this->contract_date >= now()->toDateString();
    }

    /**
     * Scope a query to only include active contract items.
     *
     * Active means contract_date is null or contract_date >= current date
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('contract_date')
                ->orWhere('contract_date', '>=', now()->toDateString());
        });
    }

    /**
     * ItemTransaction item has item id
     *
     * */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * partner
     * */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Calculate Total Share Holders for an item
     * Date range not considered contract_date >= current date

     *
     * @return array [item_id => total]
     */
    public static function getActiveShareholderCounts($itemIds)
    {
        return self::whereIn('item_id', $itemIds)
            ->where(function ($query) {
                $query->where('contract_date', '>=', now())
                    ->orWhereNull('contract_date');
            })
            ->selectRaw('item_id, COUNT(*) as total')
            ->groupBy('item_id')
            ->pluck('total', 'item_id');
    }
}
