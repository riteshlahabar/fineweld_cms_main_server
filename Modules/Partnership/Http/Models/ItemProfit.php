<?php

namespace Modules\Partnership\Http\Models;

use App\Models\Sale\Sale;
use App\Models\Sale\SaleReturn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemProfit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_date',
        'sale_id',
        'sale_return_id',
        'item_id',
        'purchase_price',
        'unit_price',
        'tax_amount',
        'discount_amount',
        'quantity',
        'total',
        'received_amount', // sale invoice payment received from customer
        'paid_amount', // sale return payment paid to customer
        'gross_profit',
        'net_profit',
    ];

    public function profitTransaction(): HasMany
    {
        return $this->hasMany(PartnerProfitShare::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }
}
