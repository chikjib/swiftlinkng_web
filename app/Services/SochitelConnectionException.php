<?php

namespace App\Services;

/** A safe configuration error that may be shown to administrators. */
class SochitelConnectionException extends \RuntimeException
{
    public static function response(int $httpStatus, ?int $code = null, string $protocol = 'REST'): self
    {
        $setting = $protocol === 'ARTX' ? 'SOCHITEL_ARTX_URL' : 'SOCHITEL_BASE_URL';
        $detail = match (true) {
            $code === null && $httpStatus === 404 => "The API endpoint was not found. Check {$setting} against the URL assigned by Sochitel.",
            $protocol === 'REST' && $code === 3 => 'Check the numeric Sochitel account user ID.',
            $protocol === 'REST' && $code === 9 => 'The RSA signature was rejected. Check the saved private key and register its matching public key in the Sochitel portal.',
            $protocol === 'REST' && $code === 10 => 'Allow the backend server public IP address in the Sochitel portal.',
            $protocol === 'REST' && in_array($code, [7, 8], true) => 'Check that the backend server clock is accurate and synchronized to UTC.',
            $protocol === 'ARTX' && $code === 73 => 'This account requires a client TLS certificate. Configure SOCHITEL_ARTX_CERTIFICATE and SOCHITEL_ARTX_SSL_KEY.',
            $httpStatus === 429 => 'Sochitel is rate limiting requests. Wait before trying again.',
            $code !== null => 'Check the saved credentials, selected protocol and account permissions with Sochitel.',
            default => "The endpoint did not return a valid {$protocol} response. Check {$setting} and the selected protocol.",
        };
        return new self("Sochitel {$protocol} connection failed (HTTP {$httpStatus}".($code !== null ? ", code {$code}" : '')."). {$detail}");
    }
}
