<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MonnifyTransactions
{
    public function verify(string $reference): array
    {
        $base = rtrim(config('app.monnifyBaseUrl'), '/');
        $login = Http::withBasicAuth(config('app.monnifyAPIKey'), config('app.monnifySecretKey'))
            ->acceptJson()->timeout(30)->post($base.'/api/v1/auth/login')->throw()->json();
        $token = data_get($login, 'responseBody.accessToken');
        if (($login['requestSuccessful'] ?? false) !== true || !is_string($token) || $token === '') {
            throw new \RuntimeException('Monnify authentication failed.');
        }
        $result = Http::withToken($token)->acceptJson()->timeout(30)
            ->get($base.'/api/v2/transactions/'.rawurlencode($reference))->throw()->json();
        $body = $result['responseBody'] ?? [];
        if (($result['requestSuccessful'] ?? false) !== true || (string) ($result['responseCode'] ?? '') !== '0') {
            throw ValidationException::withMessages(['transaction_reference' => $result['responseMessage'] ?? 'Monnify could not verify this transaction.']);
        }
        if (($body['transactionReference'] ?? null) !== $reference || ($body['paymentStatus'] ?? '') !== 'PAID') {
            throw ValidationException::withMessages(['transaction_reference' => 'The reference must match a successful PAID transaction.']);
        }
        return $body;
    }
}
