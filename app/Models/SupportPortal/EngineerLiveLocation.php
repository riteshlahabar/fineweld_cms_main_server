<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;

class EngineerLiveLocation extends Model
{
    protected $table = 'engineer_live_locations';

    protected $fillable = [
        'employee_id',
        'employee_name',
        'latitude',
        'longitude',
        'address',
        'status'
    ];
}
