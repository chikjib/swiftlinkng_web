<?php

return [
    'api_key' => env('VTUGATE_API_KEY', ''),
    'base_url' => 'https://api.vtugate.com/api/v1/international',
    // Use the API endpoint assigned to your account, not the client portal URL.
    'sochitel_base_url' => env('SOCHITEL_BASE_URL', ''),
];
