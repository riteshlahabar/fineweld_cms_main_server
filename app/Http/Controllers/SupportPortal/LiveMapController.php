<?php

namespace App\Http\Controllers\SupportPortal;

use App\Http\Controllers\Controller;
use App\Models\SupportPortal\EngineerLiveLocation;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class LiveMapController extends Controller
{
   
public function liveMapData(): JsonResponse
{
    $engineers = EngineerLiveLocation::whereIn('status', ['active', 'moving'])
        ->whereDate('updated_at', Carbon::today()) // only today
        ->get()
        ->map(function ($engineer) {
            return [
                'id'      => $engineer->employee_id,
                'name'    => $engineer->employee_name,
                'lat'     => (float) $engineer->latitude,
                'lng'     => (float) $engineer->longitude,
                'status'  => $engineer->status,
                'address' => $engineer->address ?? 'Location updating...',
            ];
        });

    return response()->json([
        'status'    => true,
        'engineers' => $engineers,
        'tickets'   => [],
    ]);
}

}
