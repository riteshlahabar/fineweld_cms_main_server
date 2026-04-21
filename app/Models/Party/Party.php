<?php

namespace App\Models\Party;

use App\Models\Currency;
use App\Models\User;
use App\Services\PartyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Party extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
   protected $fillable = [
    // ✅ FORM FIELDS ONLY
    'company_name',
    'company_type',
    'vendor_type',
    'company_pan',
    'company_gst',
    'company_tan',
    'company_msme',
    'date_of_incorporation',
    
    'primary_name',
    'primary_email',
    'primary_mobile',
    'primary_whatsapp',
    'primary_dob',
    
    'secondary_name',
    'secondary_email',
    'secondary_mobile',
    'secondary_whatsapp',
    'secondary_dob',
    
    'bank_name',
    'bank_branch',
    'bank_account_no',
    'ifsc_code',
    'micr_code',
    
    // ✅ YOUR FORM HAS THESE
    'billing_address',
    'shipping_address',
    
    'pan_document',
    'tan_document',
    'gst_document',
    'msme_document',
    'cancelled_cheque',
    
    // ✅ COMMON
    'party_type',
    'status',
    'default_party',
    'currency_id',
    'created_by',
    'updated_by',
];


    /**
     * Insert & update User Id's
     */
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
     */
    public function transaction(): MorphMany
    {
        return $this->morphMany(PartyTransaction::class, 'transaction');
    }

    /**
     * Updated fullname for vendors
     */
    public function getFullNameAttribute()
    {
        if ($this->company_name) {
            return $this->company_name;
        }
        return $this->company_name . ' ' . $this->last_name;
    }

    public function getPartyTotalDueBalance()
    {
        $partyBalance = new PartyService();
        return $partyBalance->getPartyBalance([$this->id]);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Scopes
     */
    public function scopeVendor($query)
    {
        return $query->where('party_type', 'vendor');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Accessors
     */
    public function getStatusTextAttribute()
    {
        return $this->status ? 'Active' : 'Inactive';
    }

    public function getVendorTypeTextAttribute()
    {
        return match($this->vendor_type) {
            'customer' => 'Customer',
            'supplier' => 'Supplier',
            'both' => 'Both',
            default => 'N/A'
        };
    }
    
    public function getFullName()
{
    return $this->company_name ?: $this->primary_name ?: 'N/A';
}



    /**
     * Casts
     */
    protected $casts = [
        'date_of_incorporation' => 'date',
        'primary_dob' => 'date',
        'secondary_dob' => 'date',
        'status' => 'boolean',
        'default_party' => 'boolean',
        'is_set_credit_limit' => 'boolean',
    ];
}
