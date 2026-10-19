<?php

return [
    'api_key' => env('VTUGATE_API_KEY', ''),
    'base_url' => 'https://api.vtugate.com/api/v1/international',
    // Use the API endpoint assigned to your account, not the client portal URL.
    'sochitel_artx_url' => env('SOCHITEL_ARTX_URL', ''),
    'sochitel_artx_certificate' => env('SOCHITEL_ARTX_CERTIFICATE'),
    'sochitel_artx_ssl_key' => env('SOCHITEL_ARTX_SSL_KEY'),
    'sochitel_base_url' => env('SOCHITEL_BASE_URL', ''),
];
