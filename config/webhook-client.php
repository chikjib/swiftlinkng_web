<?php

return [
    'configs' => [
        [

            'name' => 'monify-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'monnify-signature',
            'signature_validator' => \App\MonnifySignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\ProcessWebhookJob::class,
        ],
        [

            'name' => 'budpay-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'budpay-signature',
            'signature_validator' => \App\BudpaySignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\BudpayProcessWebhookJob::class,
        ],
        
        [

            'name' => 'rehoboth-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'x-auth-signature',
            'signature_validator' => \App\RehobothSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\ProcessRehobothWebhookJob::class,
        ],

        [

            'name' => 'providus-webhook',
            'signing_secret' => env('PROVIDUS_CLIENT_SECRET'),
            // 'client_id' => env('PROVIDUS_CLIENT_ID'),
            'signature_header_name' => 'x-auth-signature',
            'signature_validator' => \App\ProvidusSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \App\Jobs\ProvidusResponseWebhook::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\ProcessProvidusWebhookJob::class,
        ],
         [
            'name' => 'palmpay-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'sign',
            'signature_validator' => \App\PalmPaySignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\PalmpayProcessWebhookJob::class,
        ],
        [
            'name' => 'opay-wallet-webhook',
            'signing_secret' => env('OPAY_WALLET_BRANCH_ID'),
            'signature_header_name' => 'X-Opay-Tranid',
            'signature_validator' => \App\OpayWalletWebhookValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \App\Webhooks\OpayWalletWebhookResponse::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => ['X-Opay-Tranid', 'merchantId'],
            'process_webhook_job' => \App\Jobs\ProcessOpayWalletDeposit::class,
        ],


        [
            'name' => 'rave-webhook',
            'signing_secret' => env('RAVE_CLIENT_SECRET'),
            'signature_header_name' => 'verif-hash',
            'signature_validator' => \App\FlutterWaveSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\Jobs\FlutterWaveProcessWebhookJob::class,
        ],
        [

            'name' => 'gtsquadco-webhook',
            'signing_secret' => env('gtsquadcoAPIKey'),
            'signature_header_name' => 'x-squad-signature',
            'signature_validator' => \App\SquadCoSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\SquadCoProcessWebhookJob::class,
        ],

        [

            'name' => 'ogadam-webhook',
            'signing_secret' => env('ogaDamToken'),
            'signature_header_name' => 'x-signature',
            'signature_validator' => \App\OgaDamSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\OgaDamProcessWebhookJob::class,
        ],
        [

            'name' => 'autosync-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'x-signature',
            'signature_validator' => \App\AutoSyncSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\AutoSyncWebhookJob::class,
        ],
        [

            'name' => 'zoe-data-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'x-signature',
            'signature_validator' => \App\ZoeDataSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\ZoeDataWebhookJob::class,
        ],
        [

            'name' => 'sim-server-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'x-signature',
            'signature_validator' => \App\SimServerSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\SimServerWebhookJob::class,
        ],
        [

            'name' => 'vtuplug-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'x-signature',
            'signature_validator' => \App\AutoSyncSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\VtuPlugWebhookJob::class,
        ],
        [

            'name' => 'naijasub-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'x-signature',
            'signature_validator' => \App\NaijaSubSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\NaijaSubWebhookJob::class,
        ],
        [

            'name' => 'coolsub-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'x-signature',
            'signature_validator' => \App\CoolSubSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\CoolSubWebhookJob::class,
        ],
        [

            'name' => 'amakasub-webhook',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'x-signature',
            'signature_validator' => \App\AmakaSubSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\AmakaSubWebhookJob::class,
        ],
        [
            'name' => 'tbch-webhook',
            'signing_secret' => env('tbchportalToken'),
            'signature_header_name' => 'tbch-hash',
            'signature_validator' => \App\TbchSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\Jobs\TbchPortalWebhookJob::class,
        ],
        [
            'name' => 'smeplug-webhook',
            'signing_secret' => env('smePlugPrivateKey'),
            'signature_header_name' => 'smeplug-hash',
            'signature_validator' => \App\SmeplugSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\Jobs\SmePlugWebhookJob::class,
        ]



    ],
];
