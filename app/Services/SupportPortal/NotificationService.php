<?php

namespace App\Services\SupportPortal;

use App\Models\SupportPortal\EngineerDevice;
use App\Models\SupportPortal\AdminDevice;
use App\Models\SupportPortal\CustomerDevice;

class NotificationService
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function notifyAdmins($title, $body, $data = [])
    {
        \Log::info('notifyAdmins() method entered');
        $tokens = AdminDevice::pluck('fcm_token')->toArray();
        \Log::info('Admin tokens fetched', ['count' => count($tokens)]);

        $this->firebase->sendToTokens($tokens, $title, $body, $data);
         
         $response = $this->firebase->sendToTokens($tokens, $title, $body, $data);
    \Log::info('Firebase response', ['response' => $response]);
    }

    public function notifyEngineer($engineerId, $title, $body, $data = [])
    {
        $tokens = EngineerDevice::where('engineer_id', $engineerId)
            ->pluck('fcm_token')
            ->toArray();

        $this->firebase->sendToTokens($tokens, $title, $body, $data);
    }

    public function notifyCustomer($customerId, $title, $body, $data = [])
    {
        $tokens = CustomerDevice::where('customer_id', $customerId)
            ->pluck('fcm_token')
            ->toArray();

        $this->firebase->sendToTokens($tokens, $title, $body, $data);
    }
}