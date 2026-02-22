<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Twilio, Retell AI, payment gateways, etc.
    |
    */

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
        'sms_status_webhook' => env('TWILIO_SMS_STATUS_WEBHOOK'),
    ],

    'retell' => [
        'api_key' => env('RETELL_API_KEY'),
        'api_url' => env('RETELL_API_URL', 'https://api.retellai.com'),
    ],

    'payment' => [
        'base_url' => env('PAYMENT_BASE_URL', env('APP_URL') . '/pagamento'),
        'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'link_generico'),
        'default_expiration_days' => env('PAYMENT_EXPIRATION_DAYS', 7),
    ],

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
    ],

    'gerencianet' => [
        'client_id' => env('GERENCIANET_CLIENT_ID'),
        'client_secret' => env('GERENCIANET_CLIENT_SECRET'),
        'sandbox' => env('GERENCIANET_SANDBOX', true),
        'certificate_path' => env('GERENCIANET_CERTIFICATE_PATH'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

];
