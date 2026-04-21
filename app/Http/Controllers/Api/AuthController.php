<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
        ]);

        try {
            // ✅ CHECK PARTY USING CORRECT TABLE & COLUMN
            $party = DB::table('parties')
                ->where('primary_mobile', $request->mobile)
                ->first();

            if (!$party) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mobile number not registered',
                ], 404);
            }

            // ✅ HARD-CODED OTP (TEMP)
            $otp = '123456';

            return response()->json([
                'status' => true,
                'message' => 'OTP sent successfully',
                'otp' => $otp, // remove later in production
            ], 200);

        } catch (\Exception $e) {
            // 🔴 THIS IS WHY YOU WERE GETTING 500
            return response()->json([
                'status' => false,
                'message' => 'Server error',
                'error' => $e->getMessage(), // helpful for now
            ], 500);
        }
    }
    
    public function verifyOtp(Request $request)
{
    $request->validate([
        'mobile' => 'required|digits:10',
        'otp' => 'required|digits:6',
    ]);

    try {
        // ✅ Check party
        $party = \DB::table('parties')
            ->where('primary_mobile', $request->mobile)
            ->first();

        if (!$party) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        // ✅ Hard-coded OTP check
        if ($request->otp !== '123456') {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => [
                'party_id' => $party->id,
                'company_name' => $party->company_name,
                'primary_mobile' => $party->primary_mobile,
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Server error',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function saveCustomerToken(Request $request)
{
    $request->validate([
        'customer_id' => 'required',
        'fcm_token'   => 'required'
    ]);

    \App\Models\SupportPortal\CustomerDevice::updateOrCreate(
        [
            'customer_id' => $request->customer_id,
            'fcm_token'   => $request->fcm_token
        ],
        []
    );

    return response()->json(['status' => true]);
}

}
