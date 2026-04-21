<?php

namespace App\Models\Items;

use App\Traits\FormatsDateInputs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemBatchMaster extends Model
{
    use FormatsDateInputs;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_id',
        'batch_no',
        'mfg_date',
        'exp_date',
        'model_no',
        'mrp',
        'color',
        'size',
        'stock',
    ];

    /**
     * This method calling the Trait FormatsDateInputs
     *
     * @return null or string
     *              Use it as formatted_mfg_date
     * */
    public function getFormattedMfgDateAttribute()
    {
        return $this->toUserDateFormat($this->mfg_date); // Call the trait method
    }

    /**
     * This method calling the Trait FormatsDateInputs
     *
     * @return null or string
     *              Use it as formatted_exp_date
     * */
    public function getFormattedExpDateAttribute()
    {
        return $this->toUserDateFormat($this->exp_date); // Call the trait method
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
