<?php

namespace App\Models\Items;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSerialQuantity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'item_serial_master_id',
    ];

    /**
     * Define the relationship between Item and Unit.
     *
     * */
    public function itemSerialMaster(): BelongsTo
    {
        return $this->belongsTo(ItemSerialMaster::class);
    }

    /**
     * ItemTransaction item has item id
     *
     * */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
