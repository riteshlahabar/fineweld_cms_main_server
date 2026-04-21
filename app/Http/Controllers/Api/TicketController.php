<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportPortal\Ticket;
use App\Models\SupportPortal\MasterTicketStatus;
use Illuminate\Support\Facades\DB;
use App\Models\SupportPortal\TicketImage;
use App\Models\SupportPortal\TicketTimeline;
use App\Models\SupportPortal\AdminDevice;
use App\Services\SupportPortal\NotificationService;
use App\Services\SupportPortal\TimelineService;
use App\Services\SupportPortal\FirebaseService;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /* ================= GET MASTER DATA ================= */

    public function masters($type)
    {
        return response()->json(
            MasterTicketStatus::where('type', $type)
                ->where('is_active', 1)
                ->get()
        );
    }

    /* ================= TICKETS ================= */

    public function index()
    {
        $tickets = Ticket::with(['priority', 'status', 'serviceType', 'images'])
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
             $datePrefix = now()->format('dmY');

    $todayCount = Ticket::whereDate('created_at', now()->toDateString())
        ->lockForUpdate()
        ->count();

    $sequenceNumber = 1001 + $todayCount;

    $ticketNo = 'TCK-' . $datePrefix . '-' . $sequenceNumber;

            $ticket = Ticket::create([
                'ticket_no'          => $ticketNo,
                'party_id'           => $request->party_id,
                'product_id'         => $request->product_id,
                'problem'            => $request->problem,
                'problem_description' => $request->problem_description,
                'priority_id'        => $request->priority_id ?? 1,
                'status_id' => MasterTicketStatus::where('type', 'status')
                    ->where('slug', 'open')
                    ->value('id'),
                'service_type_id'    => $request->service_type_id ?? 1,
                'technician_id'      => null
            ]);
            
            TimelineService::add(
    $ticket->id,
    'ticket_created',
    'Ticket Created',
    'Ticket has been created successfully.',
    auth()->id()
);

            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $image) {

                    $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                    $image->move(public_path('uploads/ticket_image'), $filename);

                    TicketImage::create([
                        'ticket_id' => $ticket->id,
                        'image_path' => 'uploads/ticket_image/' . $filename
                    ]);
                }
            }
            
           

            DB::commit();
            
            \Log::info('notifyAdmins triggered');
 app(NotificationService::class)
    ->notifyAdmins(
        "New Ticket Created",
        "Ticket No: ".$ticket->ticket_no,
        ['ticket_id' => $ticket->id]
    );

            return response()->json([
                'status' => true,
                'message' => 'Ticket created successfully',
                'ticket_id' => $ticket->id
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        return response()->json(
            Ticket::with(['priority', 'status', 'serviceType', 'images', 'product', 'engineer', 'timelines'])
                ->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $ticket->update($request->only([
            'priority_id',
            'status_id',
            'service_type_id',
            'technician_id'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Ticket updated'
        ]);
    }

    public function myCustomerTickets(Request $request)
    {
        $request->validate([
            'party_id' => 'required|integer',
        ]);

        // Step 1: Get terminal status IDs dynamically
        $terminalStatusIds = MasterTicketStatus::where('type', 'status')
            ->whereIn('slug', ['closed']) // add more if business requires
            ->pluck('id')
            ->toArray();

        // Step 2: Fetch tickets excluding terminal statuses
        $tickets = Ticket::with(['product', 'status'])
            ->where('party_id', $request->party_id)
            ->whereNotIn('status_id', $terminalStatusIds)
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,

                    'ticket_number' => $ticket->ticket_no
                        ?? 'TCK-' . date('Ymd', strtotime($ticket->created_at))
                        . '-' . str_pad($ticket->id, 4, '0', STR_PAD_LEFT),

                    'product_name' => optional($ticket->product)->product_name,

                    'problem' => $ticket->problem,
                    'problem_description' => $ticket->problem_description,

                    'status' => optional($ticket->status)->name,

                    'updated_at' => $ticket->updated_at->format('d M Y, h:i A'),
                ];
            })
        ]);
    }
}
