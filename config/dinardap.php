<?php

return [
    'mock' => (bool) env('DINARDAP_MOCK', false),
    'api_url' => env('DINARDAP_API_URL'),
    'api_token' => env('DINARDAP_API_TOKEN'),
    'timeout' => (int) env('DINARDAP_DEFAULT_TIMEOUT', 8),
];
