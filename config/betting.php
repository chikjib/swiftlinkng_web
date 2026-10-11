<?php
return [
    'webhook_url' => env('PAYVESSEL_BETTING_WEBHOOK_URL'),
    'webhook_ips' => array_values(array_filter(array_map('trim', explode(',', env('PAYVESSEL_WEBHOOK_IPS', '3.255.23.38,162.246.254.36'))))),
    'base_url' => env('PAYVESSEL_BASE_URL', 'https://sandbox.payvessel.com'),
    'api_key' => env('PAYVESSEL_API_KEY'),
    'api_secret' => env('PAYVESSEL_API_SECRET'),
    // Explicitly select betting billers from PayVessel's mixed biller catalogue.
    'biller_ids' => array_values(array_filter(array_map('trim', explode(',', env('PAYVESSEL_BETTING_BILLER_IDS', ''))))),
    // Existing Swiftlink subcategory used by the shared transaction history.
    'subcategory_id' => env('PAYVESSEL_BETTING_SUBCATEGORY_ID'),
];
