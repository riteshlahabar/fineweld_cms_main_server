<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;
use App\Models\Party\Party;

class Product extends Model
{
    protected $fillable = [
        'party_id',

        // Purchase details
        'purchase_order_no',
        'purchase_order_date',
        'tax_invoice_no',
        'tax_invoice_date',

        // Product details
        'product_name',
        'model_number',
        'serial_number',

        // Installation & warranty
        'installation_date',
        'warranty_start',
        'warranty_end',

        // Meta
        'installed_by',
        'remarks',
        'product_image',
    ];

    /**
     * Product belongs to Party
     */
    public function party()
    {
        return $this->belongsTo(Party::class, 'party_id');
    }
}
