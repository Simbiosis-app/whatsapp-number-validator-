<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Validator Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default WhatsApp validator driver that will be used
    | to validate phone numbers. Currently only "rapidapi" is supported.
    |
    */
    'driver' => env('WHATSAPP_DRIVER', 'rapidapi'),

    /*
    |--------------------------------------------------------------------------
    | RapidAPI Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the RapidAPI WhatsApp validator settings.
    | Required:
    |   - key: Your RapidAPI key
    |   - host: The RapidAPI host
    |   - endpoint: The API endpoint for single number validation
    |   - bulk_endpoint: The API endpoint for bulk validation
    |
    | Optional:
    |   - timeout: Request timeout in seconds (default: 30)
    |   - retry_attempts: Number of retry attempts (default: 3)
    |
    */
    'rapidapi' => [
        'endpoint' => env('WHATSAPP_RAPIDAPI_ENDPOINT', 'https://whatsapp-number-validator3.p.rapidapi.com/WhatsappNumberHasItWithToken'),
        'bulk_endpoint' => env('WHATSAPP_RAPIDAPI_BULK_ENDPOINT', 'https://whatsapp-number-validator3.p.rapidapi.com/WhatsappNumberHasItBulkWithToken'),
        'key' => env('WHATSAPP_RAPIDAPI_KEY'),
        'host' => env('WHATSAPP_RAPIDAPI_HOST', 'whatsapp-number-validator3.p.rapidapi.com'),
        'timeout' => env('WHATSAPP_RAPIDAPI_TIMEOUT', 30),
        'retry_attempts' => env('WHATSAPP_RAPIDAPI_RETRY_ATTEMPTS', 3),
    ],
];
