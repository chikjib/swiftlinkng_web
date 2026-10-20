<?php

namespace App\Http\Middleware;

use App\Services\PaymentResponseLogger;
use Closure;
use Illuminate\Support\Str;

class LogPaymentResponses
{
    public function handle($request, Closure $next)
    {
        if (!$request->is('api/reserve/account/*', 'api/opay/wallet', 'api/opay/wallet/*',
            'api/generate-payment-link', 'api/generate-palmpay-payment-link',
            'api/palmpay-bank-transfer', 'api/payment/*', 'api/verify-payment/*')) {
            return $next($request);
        }
        $request->attributes->set(PaymentResponseLogger::CONTEXT, (string) Str::uuid());
        $started = microtime(true);
        PaymentResponseLogger::record('started', []);
        try {
            $response = $next($request);
            PaymentResponseLogger::record('app_response', array_merge([
                'http_status' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ], PaymentResponseLogger::body($response->getContent())));
            $response->headers->set('X-Payment-Request-ID', $request->attributes->get(PaymentResponseLogger::CONTEXT));
            return $response;
        } catch (\Throwable $e) {
            PaymentResponseLogger::record('exception', [
                'exception' => get_class($e), 'message' => $e->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
            throw $e;
        }
    }
}
