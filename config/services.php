<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'vue' => [
        'MIX_VUE_APP_TOKEN' => env('MIX_VUE_APP_TOKEN'),
        'MIX_FIREBASE_API_KEY' => env('MIX_FIREBASE_API_KEY'),
        'MIX_FIREBASE_APP_ID' => env('MIX_FIREBASE_APP_ID'),
        'MIX_GOOGLE_MAP_API_KEY' => env('MIX_GOOGLE_MAP_API_KEY'),
        'MIX_APP_URL' => env('MIX_APP_URL'),
    ],

    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN', env('TELEGRAM_BOT_TOKEN'))
    ],

    'whatsapp_bot_admin' => [
        'base_url' => env('WHATSAPP_BOT_URL', 'https://swiftbot.swiftlinkng.com'),
        'secret' => env('SWIFTLINK_ADMIN_API_SECRET'),
    ],

    'opay_wallet' => [
        'base_url' => env('OPAY_WALLET_BASE_URL', 'https://payapi.opayweb.com'),
        'client_auth_key' => env('OPAY_WALLET_CLIENT_AUTH_KEY'),
        'branch_id' => env('OPAY_WALLET_BRANCH_ID'),
        'root_merchant_id' => env('OPAY_WALLET_ROOT_MERCHANT_ID'),
        'operator' => env('OPAY_WALLET_OPERATOR'),
        'account_type' => env('OPAY_WALLET_ACCOUNT_TYPE', 'Merchant'),
        'send_password' => env('OPAY_WALLET_SEND_PASSWORD', 'N'),
        'fee_percent' => env('OPAY_WALLET_FEE_PERCENT', 0.4),
        'log_request_payload' => env('OPAY_WALLET_LOG_REQUEST_PAYLOAD', false),
        'opay_public_key_path' => env(
            'OPAY_WALLET_OPAY_PUBLIC_KEY_PATH',
            'storage/app/opay/opay_public.pem'
        ),
        'merchant_private_key_path' => env(
            'OPAY_WALLET_MERCHANT_PRIVATE_KEY_PATH',
            'storage/app/opay/merchant_private.pem'
        ),
        'verify_responses' => env('OPAY_WALLET_VERIFY_RESPONSES', true),
    ],



];
