<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SupportPortal\EngineerLiveLocation;

class EngineerLiveController extends Controller
{
    /**
     * Validate HRMS Token
     */
    private function validateHrmsToken($token, $employeeId)
    {
        if (!$token || !$employeeId) {
            return false;
        }

        $response = Http::withToken($token)
            ->get('https://hrms.turnkeyinfotech.live/api/attendance/status?employee_id=' . $employeeId);

        \Log::info('HRMS STATUS: ' . $response->status());
        \Log::info('HRMS BODY: ' . $response->body());

        return $response->status() === 200;
    }

    /**
     * Start Presence
     */
   public function start(Request $request)
{
    $request->validate([
        'employee_id'   => 'required',
        'employee_name' => 'required',
        'latitude'      => 'required',
        'longitude'     => 'required',
    ]);

    $token = $request->bearerToken();

    if (!$this->validateHrmsToken($token, $request->employee_id)) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    EngineerLiveLocation::updateOrCreate(
        ['employee_id' => $request->employee_id],
        [
            'employee_name' => $request->employee_name,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'address'       => $request->address ?? null,
            'status'        => 'active',
            'updated_at'    => now(), // force today timestamp
        ]
    );

    return response()->json([
        'status'  => true,
        'message' => 'Presence started successfully',
    ]);
}


    /**
     * Update Location
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'latitude'    => 'required',
            'longitude'   => 'required',
        ]);

        $token = $request->bearerToken();

        if (!$this->validateHrmsToken($token, $request->employee_id)) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $engineer = EngineerLiveLocation::where('employee_id', $request->employee_id)->first();

        if (!$engineer) {
            return response()->json([
                'status'  => false,
                'message' => 'Engineer not found',
            ], 404);
        }

        $engineer->update([
    'latitude'   => $request->latitude,
    'longitude'  => $request->longitude,
    'address'    => $request->address ?? $engineer->address,
    'status'     => 'moving',
    'updated_at' => now(), // keep fresh timestamp
]);

        return response()->json([
            'status'  => true,
            'message' => 'Location updated successfully',
        ]);
    }

    /**
     * Stop Presence
     */
   public function stop(Request $request)
{
    $request->validate([
        'employee_id' => 'required',
    ]);

    $token = $request->bearerToken();

    if (!$this->validateHrmsToken($token, $request->employee_id)) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    EngineerLiveLocation::where('employee_id', $request->employee_id)
        ->update([
            'status'     => 'offline',
            'updated_at' => now(),
        ]);

    return response()->json([
        'status'  => true,
        'message' => 'Presence stopped successfully',
    ]);
}

}
