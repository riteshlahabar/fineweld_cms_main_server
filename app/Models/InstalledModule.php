<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstalledModule extends Model
{
    protected $fillable = [
        'name',
        'version',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}
