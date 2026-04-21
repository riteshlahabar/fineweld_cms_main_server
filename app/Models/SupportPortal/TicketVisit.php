<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;
use App\Models\SupportPortal\EngineerLiveLocation;

class TicketVisit extends Model
{
    protected $fillable = [
        'ticket_id',
        'engineer_id',
        'inspection_type',
        'onsite_type',
        'description',
    ];

    public function images()
    {
        return $this->hasMany(VisitImage::class);
    }
    
     public function engineer()
{
    return $this->belongsTo(
        EngineerLiveLocation::class,
        'engineer_id',     // foreign key in ticket_visits
        'employee_id'      // column in engineer_live_locations
    );
}

    // Link visit to ticket
    public function ticket()
    {
        return $this->belongsTo(
            Ticket::class,
            'ticket_id'
        );
    }
}