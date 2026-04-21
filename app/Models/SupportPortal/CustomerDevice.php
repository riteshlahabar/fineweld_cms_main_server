<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;

class CustomerDevice extends Model
{
    protected $fillable = [
        'customer_id',
        'fcm_token'
    ];
}