<?php

namespace App\Models\SupportPortal;

use Illuminate\Database\Eloquent\Model;
use App\Models\SupportPortal\MasterTicketStatus;
use App\Models\SupportPortal\TicketImage;
use App\Models\Party\Party;
use App\Models\SupportPortal\EngineerLiveLocation;
use App\Models\SupportPortal\TicketTimeline;

class Ticket extends Model
{
    protected $table = 'tickets';

   protected $fillable = [
    'ticket_no',
    'party_id',
    'product_id',
    'problem',
    'problem_description',
    'priority_id',
    'status_id',
    'service_type_id',
    'visit_type_id',
    'technician_id',
    'closed_at'
];

public function party()
{
    return $this->belongsTo(Party::class, 'party_id');
}

public function product()
{
    return $this->belongsTo(Product::class, 'product_id');
}
public function images()
{
    return $this->hasMany(TicketImage::class);
}
    /* ================= RELATIONS ================= */

    public function priority()
    {
        return $this->belongsTo(
            MasterTicketStatus::class,
            'priority_id'
        );
    }

    public function status()
    {
        return $this->belongsTo(
            MasterTicketStatus::class,
            'status_id'
        );
    }

    public function serviceType()
    {
        return $this->belongsTo(
            MasterTicketStatus::class,
            'service_type_id'
        );
    }
    
    public function visitType()
{
    return $this->belongsTo(MasterTicketStatus::class, 'visit_type_id');
}

public function visits()
{
    return $this->hasMany(\App\Models\SupportPortal\TicketVisit::class, 'ticket_id');
}

public function engineer()
{
    return $this->belongsTo(
        EngineerLiveLocation::class,
        'technician_id',
        'employee_id'
    );
}

public function timelines()
{
    return $this->hasMany(TicketTimeline::class)
                ->orderBy('created_at');
}
}
