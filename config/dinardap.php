<?php

return [
    'mock' => (bool) env('DINARDAP_MOCK', false),
    'api_url' => env('DINARDAP_API_URL'),
    'api_token' => env('DINARDAP_API_TOKEN'),
    'api_user' => env('DINARDAP_API_USER', ''),
    'api_ip' => env('DINARDAP_API_IP', '127.0.0.1'),
    'timeout' => (int) env('DINARDAP_DEFAULT_TIMEOUT', 8),
];
