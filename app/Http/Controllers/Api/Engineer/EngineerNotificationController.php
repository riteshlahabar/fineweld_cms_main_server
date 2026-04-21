<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportPortal\EngineerDevice;

class EngineerNotificationController extends Controller
{
   public function saveFcmToken(Request $request)
{
    $request->validate([
        'engineer_id' => 'required|integer',
        'fcm_token'   => 'required|string'
    ]);

    EngineerDevice::updateOrCreate(
        [
            'engineer_id' => $request->engineer_id
        ],
        [
            'fcm_token'   => $request->fcm_token,
            'device_type' => 'android'
        ]
    );

    return response()->json([
        'status' => true,
        'message' => 'FCM token saved successfully'
    ]);
}
}
