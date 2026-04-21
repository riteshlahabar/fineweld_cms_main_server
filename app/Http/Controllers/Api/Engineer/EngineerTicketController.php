<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportPortal\Ticket;


class EngineerTicketController extends Controller
{
    public function getMyTickets(Request $request)
{
    $request->validate([
        'engineer_id' => 'required'
    ]);

    $engineerId = $request->engineer_id;

    $current = Ticket::with([
    'party',
    'product',
    'priority',
    'status',
    'images'
])
->where('technician_id', $engineerId)
->where('is_active', 1)
->whereNull('closed_at')
->first();



   $pending = Ticket::with([
    'party',
    'product',
    'priority',
    'status',
    'images'
])
->where('technician_id', $engineerId)
->where('is_active', 0)
->whereNull('closed_at')
->orderBy('created_at', 'desc')
->get();

    return response()->json([
        'status' => true,
        'current' => $current,
        'pending' => $pending
    ]);
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

public function todayClosedTickets()
{
   $tickets = Ticket::with([
        'party',
        'product',
        'priority',
        'status'
    ])
    ->whereNotNull('closed_at')
    ->whereDate('closed_at', today())
    ->get();

    return response()->json([
        'status' => true,
        'data' => $tickets
    ]);
}

}
