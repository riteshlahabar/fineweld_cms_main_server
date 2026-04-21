<?php

namespace App\Http\Controllers;

use App\Enums\App;
use App\Http\Requests\SmsRequest;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;

class SmsController extends Controller
{
    protected $appSettingsRecordId;

    protected $companyId;

    private $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->appSettingsRecordId = App::APP_SETTINGS_RECORD_ID->value;
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
        $this->smsService = $smsService;
    }

    public function create()
    {
        return view('sms.create');
    }

    /**
     * Send SMS
     *
     * return @return \Illuminate\Http\JsonResponse
     *
     * */
    public function send(SmsRequest $request): JsonResponse
    {

        $validatedData = $request->validated();

        return $this->smsService->send($validatedData);
    }
}
