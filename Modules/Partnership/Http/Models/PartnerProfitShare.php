<?php

namespace Modules\Partnership\Http\Models;

use App\Models\Items\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerProfitShare extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_date',
        'item_profit_id',
        'partner_id',
        'contract_id',
        'share_type',
        'share_value',
        'distributed_profit_amount', // partner profit amount
        'distributed_received_amount', // amount received from customer
        'distributed_paid_amount', // amount paid to customer
        'sale_id',
        'sale_return_id',
        'item_id',
    ];

    public function profitTransaction(): BelongsTo
    {
        return $this->belongsTo(ItemProfit::class, 'item_profit_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * ItemTransaction item has item id
     *
     * */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
