<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;

class EngineerDevice extends Model
{
    protected $table = 'engineer_devices';

    protected $fillable = [
        'engineer_id',
        'fcm_token',
        'device_type',
    ];
}
