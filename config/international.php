<?php

return [
    'api_key' => env('VTUGATE_API_KEY', ''),
    'base_url' => 'https://api.vtugate.com/api/v1/international',
    // Production ARTX endpoint; staging must explicitly override SOCHITEL_ARTX_URL.
    'sochitel_artx_url' => env('SOCHITEL_ARTX_URL', 'https://artxh1.sochitel.com/apiv8.php'),
    'sochitel_artx_certificate' => env('SOCHITEL_ARTX_CERTIFICATE'),
    'sochitel_artx_ssl_key' => env('SOCHITEL_ARTX_SSL_KEY'),
    'sochitel_base_url' => env('SOCHITEL_BASE_URL', ''),
];
