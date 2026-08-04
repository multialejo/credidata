<?php

return [
    'api_url' => env('PAYPHONE_API_URL', 'https://pay.payphonetodoesposible.com/api'),
    'store_id' => env('PAYPHONE_STORE_ID'),
    'api_token' => env('PAYPHONE_API_TOKEN'),
    'mock' => (bool) env('PAYPHONE_MOCK', false),
    'timeout' => (int) env('PAYPHONE_TIMEOUT', 10),
    'currency' => env('PAYPHONE_CURRENCY', 'USD'),
    'reference' => env('PAYPHONE_REFERENCE', 'CrediData recarga'),
    'response_url' => env('PAYPHONE_RESPONSE_URL', 'http://localhost/dashboard/recargas/payphone/return'),
    'cancellation_url' => env('PAYPHONE_CANCELLATION_URL', 'http://localhost/dashboard/recargas/payphone/cancel'),
];
