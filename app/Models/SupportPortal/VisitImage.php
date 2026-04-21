<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;

class VisitImage extends Model
{
    protected $fillable = [
        'ticket_visit_id',
        'image_path',
        'image_type',
    ];

    public function visit()
    {
        return $this->belongsTo(TicketVisit::class);
    }
}