<?php

namespace App\Http\Controllers\Supportportal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SupportPortal\Ticket;
use App\Models\SupportPortal\MasterTicketStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;


class TicketController extends Controller
{
    /* ================= WEB VIEWS ================= */

public function list()
{
   $closedStatusId = MasterTicketStatus::where('type','status')
                    ->where('slug','closed')
                    ->value('id');

$tickets = Ticket::with([
    'priority',
    'status',
    'serviceType',
    'visitType',
    'images',
    'party',
    'product',
    'engineer'
])
->where(function ($query) use ($closedStatusId) {

    $query->where('status_id','!=',$closedStatusId)
          ->orWhere(function ($q) use ($closedStatusId) {
              $q->where('status_id',$closedStatusId)
                ->whereDate('updated_at', Carbon::today());
          });

})
->latest()
->get();

    $statuses = MasterTicketStatus::where('type','status')
                    ->where('is_active',1)
                    ->get();
                    
    $priorities = MasterTicketStatus::where('type','priority')
                ->where('is_active',1)
                ->get();                

    // 🔥 ADD THIS HERE
    $engineers = \DB::table('engineer_live_locations')
                ->select('employee_id','employee_name')
                ->whereDate('updated_at', \Carbon\Carbon::today())
                ->get();

    return view('support-portal.ticket.list', compact('tickets','statuses','priorities','engineers'));
}

    public function create()
    {
        $priorities = MasterTicketStatus::where('type', 'priority')
            ->where('is_active', 1)->get();

        $statuses = MasterTicketStatus::where('type', 'status')
            ->where('is_active', 1)->get();

        $services = MasterTicketStatus::where('type', 'service_type')
            ->where('is_active', 1)->get();
        
        $visitTypes = MasterTicketStatus::where('type', 'visit_type')
    ->where('is_active', 1)->get();    

       return view('support-portal.ticket.list', compact('tickets','statuses','engineers'));
    }

    public function details($id)
    {
        $ticket = Ticket::findOrFail($id);
        return view('support-portal.ticket.details', compact('ticket'));
    }



public function assign()
{
    // Engineers from live tracking table
    $technicians = DB::table('engineer_live_locations')
        ->select('employee_id','employee_name')
        ->whereDate('updated_at', Carbon::today())
        ->get();

    // Add job counts for each engineer
    $technicians = $technicians->map(function ($tech) {

        $activeJobs = Ticket::where('technician_id', $tech->employee_id)
            ->whereHas('status', function ($q) {
                $q->whereIn('slug', ['assigned','in_progress','visited']);
            })
            ->count();

        $currentTicket = Ticket::where('technician_id', $tech->employee_id)
            ->whereHas('status', function ($q) {
                $q->whereIn('slug', ['assigned','in_progress']);
            })
            ->latest()
            ->first();

        $tech->active_jobs = $activeJobs;
        $tech->current_ticket = $currentTicket;

        return $tech;
    });

    $totalTechnicians = $technicians->count();

    $totalJobs = Ticket::whereHas('status', function ($q) {
        $q->whereIn('slug', ['assigned','in_progress','visited']);
    })->count();

    $busyTechnicians = $technicians->filter(function ($t) {
        return $t->active_jobs >= 3;
    })->count();

    $availableTechnicians = $totalTechnicians - $busyTechnicians;

    return view('support-portal.technician.assign', compact(
        'technicians',
        'totalTechnicians',
        'totalJobs',
        'busyTechnicians',
        'availableTechnicians'
    ));
}

    public function track()
    {
        return view('support-portal.technician.live');
    }
    
    
    public function closed()
{
    $closedStatusId = MasterTicketStatus::where('type','status')
                        ->where('slug','closed')
                        ->value('id');

    $tickets = Ticket::with([
        'priority',
        'status',
        'party',
        'product',
        'engineer',
        'images',
        'visits',
        'timelines', // 🔥 important
    ])
    ->where('status_id',$closedStatusId)
    ->latest()
    ->get();

    return view('support-portal.ticket.closed', compact('tickets'));
}



    /* ================= CRUD ================= */

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required',
            'contact_name' => 'required',
            'mobile' => 'required',
            'address' => 'required',
            'problem' => 'required',
            'priority_id' => 'required|exists:masters_ticket_status,id',
            'status_id' => 'required|exists:masters_ticket_status,id',
            'service_type_id' => 'required|exists:masters_ticket_status,id',
            'visit_type_id' => 'nullable|exists:masters_ticket_status,id',
        ]);

        $ticket = Ticket::create([
            'ticket_no' => 'TKT-' . time(),
            'company_name' => $request->company_name,
            'contact_name' => $request->contact_name,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'problem' => $request->problem,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'service_type_id' => $request->service_type_id,
            'visit_type_id' => $request->visit_type_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Ticket created successfully',
            'data' => $ticket
        ]);
    }



    public function update(Request $request)
    {
        $ticket = Ticket::findOrFail($request->id);

        $ticket->update($request->only([
            'priority',
            'status',
            'service_type',
            'visit_type_id',
            'technician_id'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Ticket updated'
        ]);
    }



    public function delete(Request $request)
    {
        Ticket::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Tickets deleted');
    }



    /* ================= DATATABLE / CARDS ================= */

    public function datatableList()
    {
        $tickets = Ticket::latest()->get();

        return response()->json($tickets);
    }
    
    

    public function changeStatus(Request $request)
    {
        $ticket = Ticket::findOrFail($request->ticket_id);

        $currentStatus = $ticket->status->slug;

        $nextStatusSlug = match ($currentStatus) {
            'open' => 'assigned',
            'assigned' => 'in_progress',
            'in_progress' => 'completed',
            default => null
        };

        if (!$nextStatusSlug) {
            return response()->json(['status' => false]);
        }

        $nextStatus = MasterTicketStatus::where('type', 'status')
            ->where('slug', $nextStatusSlug)
            ->first();

        $ticket->status_id = $nextStatus->id;
        $ticket->save();
        // ADD TIMELINE ENTRY
app(\App\Services\SupportPortal\TimelineService::class)
    ->add(
        $ticket->id,
        'engineer_assigned',
        'Engineer Assigned',
        'Engineer has been assigned and visit scheduled.',
        auth()->id()
    );

        return response()->json([
            'status' => true,
            'new_status' => $nextStatus->name,
            'new_color' => $nextStatus->ui_class,
            'next_status' => $nextStatus->name
        ]);
    }
    
    
    
   public function assignEngineer(Request $request)
{
    $request->validate([
        'ticket_id' => 'required|exists:tickets,id',
        'engineer_id' => 'required',
        'schedule_date' => 'required',
        'schedule_time' => 'required'
    ]);

    $ticket = Ticket::findOrFail($request->ticket_id);

    $scheduledAt = Carbon::parse(
        $request->schedule_date . ' ' . $request->schedule_time
    );

    $ticket->technician_id = $request->engineer_id;
    $ticket->scheduled_at = $scheduledAt;

    // Assigned status
    $assignedStatus = MasterTicketStatus::where('type','status')
                        ->where('slug','assigned')
                        ->first();

    if($assignedStatus){
        $ticket->status_id = $assignedStatus->id;
    }

    $ticket->save();
    
     app(\App\Services\SupportPortal\TimelineService::class)
        ->add(
            $ticket->id,
            'engineer_assigned',
            'Engineer Assigned',
            'Engineer has been assigned and visit scheduled.',
            auth()->id()
        );

    $notificationService = app(\App\Services\SupportPortal\NotificationService::class);


$notificationService->notifyEngineer(
    $request->engineer_id,
    "New Ticket Assigned",
    "Ticket No: ".$ticket->ticket_no,
    ['ticket_id' => (string) $ticket->id]
);


$notificationService->notifyCustomer(
    $ticket->party_id,
    "Engineer Assigned",
    "Engineer scheduled for your ticket.",
    ['ticket_id' => (string) $ticket->id]
);

    return response()->json([
        'status' => true
    ]);
}


public function visits($id)
{
    $ticket = Ticket::with([
        'visits.images',
        'visits.engineer'
    ])->findOrFail($id);

    return view('support-portal.ticket.visits', compact('ticket'));
}


}
