<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;

class TicketTimeline extends Model
{
    protected $fillable = [
        'ticket_id',
        'event_type',
        'title',
        'description',
        'performed_by',
        'meta_data',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}