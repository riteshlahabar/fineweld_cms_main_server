<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportPortal\TicketVisit;
use App\Models\SupportPortal\VisitImage;
use App\Models\SupportPortal\Ticket;
use Illuminate\Support\Facades\Auth;
use App\Models\SupportPortal\MasterTicketStatus;
use App\Models\SupportPortal\TicketClosure;
use App\Services\SupportPortal\NotificationService;
use App\Services\SupportPortal\TimelineService;
use App\Models\SupportPortal\TicketTimeline;


class TicketVisitController extends Controller
{
     public function index($ticketId)
    {
        $visits = TicketVisit::with('images')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($visits);
    }
    
    public function store(Request $request)
{
    $ticket = Ticket::findOrFail($request->ticket_id);
    
    $request->validate([
    'employee_id' => 'required',
    'ticket_id' => 'required',
    'inspection_type' => 'required',
    'description' => 'required_if:inspection_type,phonic'
]);
    
     $closedStatus = MasterTicketStatus::where('slug', 'closed')
    ->where('type', 'status')
    ->first();

if ($ticket->status_id == $closedStatus->id) {
    return response()->json([
        'status' => false,
        'message' => 'Ticket already closed. Cannot add new visit.'
    ], 400);
}

    // 1️⃣ Create Visit (FOR BOTH TYPES)
    $visit = TicketVisit::create([
    'ticket_id' => $ticket->id,
    'engineer_id' => $request->employee_id, // 🔥 FIXED
    'inspection_type' => $request->inspection_type,
    'onsite_type' => $request->onsite_type ?? null,
    'description' => $request->description,
]);

// UPDATE VISIT TYPE IN TICKET
$visitType = MasterTicketStatus::where('slug', $request->onsite_type)
    ->where('type', 'visit_type')
    ->first();

if ($visitType) {
    $ticket->update([
        'visit_type_id' => $visitType->id
    ]);
}

    // 2️⃣ Save Images (if any)
   if ($request->hasFile('service_images')) {

    foreach ($request->file('service_images') as $image) {

        $fileName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();

        $image->move(public_path('uploads/visits'), $fileName);

        VisitImage::create([
            'ticket_visit_id' => $visit->id,
            'image_path' => 'uploads/visits/'.$fileName,
            'image_type' => 'service',
        ]);
    }
}

   if ($request->hasFile('machine_images')) {

    foreach ($request->file('machine_images') as $image) {

        $fileName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();

        $image->move(public_path('uploads/visits'), $fileName);

        VisitImage::create([
            'ticket_visit_id' => $visit->id,
            'image_path' => 'uploads/visits/'.$fileName,
            'image_type' => 'machine',
        ]);
    }
}

// Timeline
TimelineService::add(
    $ticket->id,
    'visit_added',
    'Engineer Visit Added',
    'Engineer performed '.$request->inspection_type.' inspection.',
    $request->employee_id
);

// Send Notification to Admin
$notificationService = app(\App\Services\SupportPortal\NotificationService::class);

// Notify Admin
$notificationService->notifyAdmins(
    "Engineer Visit Saved",
    "Engineer added visit for Ticket No: ".$ticket->ticket_no,
    [
        'ticket_id' => (string) $ticket->id,
        'visit_id'  => (string) $visit->id
    ]
);

// Notify Customer
$notificationService->notifyCustomer(
    $ticket->party_id,
    "Service Update",
    "Engineer visited for your ticket ".$ticket->ticket_no,
    [
        'ticket_id' => (string) $ticket->id
    ]
);
    // 3️⃣ Update Ticket Status Logic

    // Visual Inspection → Visited
if ($request->inspection_type === 'onsite' &&
    $request->onsite_type === 'visual') {

    $this->changeStatus($ticket, 'visited');
}

    return response()->json([
        'status' => true,
        'visit_id' => $visit->id,
        'message' => 'Visit saved successfully'
    ]);
}
    
    public function saveVisit(Request $request)
{
    $visitSavedStatus = MasterTicketStatus::where('slug', 'visit_saved')
        ->where('type', 'status')
        ->first();

    $visualType = MasterTicketStatus::where('slug', 'visual')
        ->where('type', 'service_type')
        ->first();

    $visit = TicketVisit::create([
        'ticket_id' => $request->ticket_id,
        'engineer_id' => auth()->id(),
        'description' => $request->description
    ]);

    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->store('visits', 'public');

            VisitImage::create([
                'ticket_visit_id' => $visit->id,
                'image_path' => $path,
            ]);
        }
    }

   $ticket = Ticket::find($request->ticket_id);

$this->changeStatus($ticket, 'visited');

    return response()->json([
        'status' => true,
        'message' => 'Visit saved successfully'
    ]);
}

public function saveServiceComplete(Request $request)
{
    $serviceCompleteType = MasterTicketStatus::where('slug', 'service_complete')
        ->where('type', 'service_type')
        ->first();

    $visit = TicketVisit::create([
        'ticket_id' => $request->ticket_id,
        'engineer_id' => auth()->id(),
        'description' => $request->description
    ]);

   if ($request->hasFile('service_images')) {

    foreach ($request->file('service_images') as $image) {

        $fileName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();

        $image->move(public_path('uploads/visits'), $fileName);

        VisitImage::create([
            'ticket_visit_id' => $visit->id,
            'image_path' => 'uploads/visits/'.$fileName,
            'image_type' => 'service',
        ]);
    }
}

    $ticket = Ticket::find($request->ticket_id);

$this->changeStatus($ticket, 'closed');

$ticket->update([
    'closed_at' => now()
]);

    return response()->json([
        'status' => true,
        'visit_id' => $visit->id
    ]);
}

public function savePhonicInspection(Request $request)
{
    $phonicType = MasterTicketStatus::where('slug', 'phonic')
        ->where('type', 'service_type')
        ->first();

    Ticket::where('id', $request->ticket_id)->update([
        'service_type_id' => $phonicType->id,
    ]);

    return response()->json([
        'status' => true
    ]);
}

public function closeTicketAfterFirebase(Request $request)
{
    try {

        $ticket = Ticket::with('party')->find($request->ticket_id);

if (!$ticket) {
    return response()->json([
        'status' => false,
        'message' => 'Ticket not found'
    ]);
}

// 🔹 OTP BYPASS NUMBER
$bypassNumber = '9234567890';

// If ticket already closed prevent duplicate closure
if ($ticket->closed_at) {
    return response()->json([
        'status' => true,
        'message' => 'Ticket already closed'
    ]);
}

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Ticket not found'
            ]);
        }

        // Get closed status
        $closedStatus = MasterTicketStatus::where('slug', 'closed')
            ->where('type', 'status')
            ->first();

        if (!$closedStatus) {
            return response()->json([
                'status' => false,
                'message' => 'Closed status not found'
            ]);
        }

        // CLOSE TICKET
        $ticket->update([
            'status_id' => $closedStatus->id,
            'closed_at' => now()
        ]);

        // TIMELINE
        TimelineService::add(
            $ticket->id,
            'ticket_closed',
            'Ticket Closed Successfully',
            'Ticket closed after Firebase OTP verification.',
            $ticket->technician_id
        );

        $notificationService = app(\App\Services\SupportPortal\NotificationService::class);

        // ADMIN NOTIFICATION
        $notificationService->notifyAdmins(
            "Ticket Closed",
            "Ticket ".$ticket->ticket_no." closed by engineer after OTP verification.",
            [
                'ticket_id' => (string) $ticket->id
            ]
        );

        // CUSTOMER NOTIFICATION
        $notificationService->notifyCustomer(
            $ticket->party_id,
            "Service Completed",
            "Your ticket ".$ticket->ticket_no." has been completed.",
            [
                'ticket_id' => (string) $ticket->id
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Ticket closed successfully'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }
}

private function changeStatus($ticket, $slug)
{
    $status = MasterTicketStatus::where('slug', $slug)
        ->where('type', 'status')
        ->first();

    if ($status) {
        $ticket->update([
            'status_id' => $status->id
        ]);
    }
}

public function setCurrentTicket(Request $request)
{
    $ticketId = $request->ticket_id;
    $engineerId = $request->engineer_id;

    // Reset all tickets
    Ticket::where('technician_id', $engineerId)
        ->update(['is_active' => 0]);

    // Set selected ticket active
    Ticket::where('id', $ticketId)
        ->update(['is_active' => 1]);

    return response()->json([
        'status' => true,
        'message' => 'Current ticket updated successfully'
    ]);
}



}
