# Payment response diagnostics

After deploying this change, retry the failing account/payment action. Each attempt
writes to Laravel's dated `storage/logs/payment-responses-YYYY-MM-DD.log` file.
Files rotate daily and are retained for 14 days. The directory stays outside the
public web root; no public log-viewing endpoint is added.

From the deployed Laravel project directory:

```sh
tail -f storage/logs/payment-responses-*.log
```

If Laravel configuration is cached, rebuild it after deploying:

```sh
php artisan config:cache
```

Coverage: OPay wallet creation/lookups, all `reserve/account/{bank}` methods
(including PalmPay, Moniepoint, Wema, GTBank, Providus and Rehoboth), Monnify/card
checkout, PalmPay card and temporary transfer accounts, and the payment
initialization/confirmation API routes. Flutter and web use these same APIs.
There is no provider response when validation rejects an attempt before a provider
call; the application response or exception is logged instead.

Entries use the same `request_id` throughout an attempt:

- `payment.started`: request route and method.
- `payment.provider_response`: provider host/operation, HTTP status and response body.
- `payment.provider_decrypted_response`: readable OPay business response.
- `payment.provider_connection_failed`: provider connection failure.
- `payment.app_response`: final application status/body and elapsed milliseconds.
- `payment.exception`: exception class/message if the request throws.

Responses include an `X-Payment-Request-ID` header for matching browser/API attempts
to log entries. Credentials, tokens, BVN/NIN, contact details and bank account
numbers are redacted. Non-JSON response bodies are summarized, and long strings
are limited. Provider codes and error messages remain available. Log write failures
do not interrupt account creation or payments. These diagnostics do not fix provider
configuration or account-creation bugs and do not run live transactions themselves.
