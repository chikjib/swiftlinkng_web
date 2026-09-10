<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'monify-webhook',
        'rehoboth-webhook',
        'rave-webhook',
        'gtsquadco-webhook',
        'providus-webhook',
        'ogadam-webhook',
        'tbch-webhook',
        'autosync-webhook',
        'smeplug-webhook',
        'wisper-webhook',
        'palmpay-webhook',
        'opay-wallet-webhook',
        'budpay-webhook',
        'vtuplug-webhook',
        'naijasub-webhook',
        'amakasub-webhook',
        'coolsub-webhook',
        'zoe-data-webhook',
        'sim-server-webhook',

    ];
}
