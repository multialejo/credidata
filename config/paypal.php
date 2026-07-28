<?php

return [
    'mode' => env('PAYPAL_MODE', 'sandbox'),
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    'mock' => (bool) env('PAYPAL_MOCK', false),
    'timeout' => (int) env('PAYPAL_TIMEOUT', 10),
    'return_url' => env('PAYPAL_RETURN_URL'),
    'cancel_url' => env('PAYPAL_CANCEL_URL'),
];
