<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;

class AdminDevice extends Model
{
    protected $fillable = [
        'admin_id',
        'fcm_token'
    ];
}