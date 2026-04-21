<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;

class TicketImage extends Model
{
    protected $fillable = [
        'ticket_id',
        'image_path'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
