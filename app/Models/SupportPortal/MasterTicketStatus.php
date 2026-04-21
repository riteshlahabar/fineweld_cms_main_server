<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;

class MasterTicketStatus extends Model
{
    protected $table = 'masters_ticket_status';

    protected $fillable = [
        'type',
        'name',
        'slug',
        'ui_class',
        'is_active'
    ];
}
