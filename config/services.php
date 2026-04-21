<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
    'google_maps' => [
    'key' => env('GOOGLE_MAPS_KEY'),
],

'fcm' => [
    'server_key' => env('FCM_SERVER_KEY'),
],

    'einvoice' => [
        'auth_url' => env('GST_EINV_AUTH_URL'),
        'generate_url' => env('GST_EINV_GENERATE_URL'),
        'cancel_url' => env('GST_EINV_CANCEL_URL'),
        'get_by_irn_url' => env('GST_EINV_GET_BY_IRN_URL'),
        'generate_ewb_by_irn_url' => env('GST_EINV_GENERATE_EWB_BY_IRN_URL'),
        'sync_gstin_url' => env('GST_EINV_SYNC_GSTIN_URL'),
        'client_id' => env('GST_EINV_CLIENT_ID'),
        'client_secret' => env('GST_EINV_CLIENT_SECRET'),
        'username' => env('GST_EINV_USERNAME'),
        'password' => env('GST_EINV_PASSWORD'),
        'gstin' => env('GST_EINV_GSTIN'),
        'request_timeout' => (int) env('GST_EINV_TIMEOUT', 30),
        'auto_generate_on_sale_save' => filter_var(env('GST_EINV_AUTO_GENERATE_ON_SALE_SAVE', false), FILTER_VALIDATE_BOOLEAN),
        'fail_on_error' => filter_var(env('GST_EINV_FAIL_ON_ERROR', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'ewaybill' => [
        'action_url' => env('GST_EWB_ACTION_URL'),
        'cancel_url' => env('GST_EWB_CANCEL_URL'),
        'get_ewaybill_url' => env('GST_EWB_GET_URL'),
    ],
];
