<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class EnsureTransactionPin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || empty($user->transaction_pin)) {
            return response()->json([
                'status' => false,
                'message' => 'Create your transaction PIN to continue.',
                'code' => 'TRANSACTION_PIN_SETUP_REQUIRED',
            ], 428);
        }

        $pin = trim((string) ($request->header('X-Transaction-Pin') ?: $request->input('transaction_pin')));
        $attemptKey = 'transaction-pin:'.$user->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return response()->json([
                'status' => false,
                'message' => 'Too many incorrect PIN attempts. Try again in '.RateLimiter::availableIn($attemptKey).' seconds.',
                'code' => 'TRANSACTION_PIN_LOCKED',
            ], 429);
        }
        if (!preg_match('/^\d{4}$/', $pin) || !Hash::check($pin, $user->transaction_pin)) {
            RateLimiter::hit($attemptKey, 300);
            return response()->json([
                'status' => false,
                'message' => 'Incorrect transaction PIN.',
                'code' => 'INVALID_TRANSACTION_PIN',
            ], 422);
        }

        RateLimiter::clear($attemptKey);

        return $next($request);
    }
}
