<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyIntegrationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'host',
        'company_name',
        'xml_port',
        'sales_ledger_name',
        'port',
        'odbc_port',
        'username',
        'password',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'status' => 'boolean',
        'xml_port' => 'integer',
        'port' => 'integer',
        'odbc_port' => 'integer',
    ];

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
}
