<?php
return [
    'base_url' => env('PAYVESSEL_BASE_URL', 'https://sandbox.payvessel.com'),
    'api_key' => env('PAYVESSEL_API_KEY'),
    'api_secret' => env('PAYVESSEL_API_SECRET'),
    // Explicitly select betting billers from PayVessel's mixed biller catalogue.
    'biller_ids' => array_values(array_filter(array_map('trim', explode(',', env('PAYVESSEL_BETTING_BILLER_IDS', ''))))),
    // Existing Swiftlink subcategory used by the shared transaction history.
    'subcategory_id' => env('PAYVESSEL_BETTING_SUBCATEGORY_ID'),
];
