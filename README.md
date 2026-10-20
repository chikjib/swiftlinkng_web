<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## AutoSync bulk SMS

To enable AutoSync for the existing SMS purchase endpoint, set the `BULKSMS`
subcategory's **description** (provider) to `AUTOSYNC` in the admin category
editor. Keep its existing products/rates configured; pricing is per recipient
per 160-character segment. `SMARTSMS` and `BULKSMSNG` remain available.

Configure the server environment:

```dotenv
AUTOSYNC_SMS_BASE_URL=https://autosyncng.com/api/v1
AUTOSYNC_SMS_API_TOKEN=your-api-token
AUTOSYNC_SMS_PIN=your-four-digit-transaction-pin
AUTOSYNC_SMS_WEBHOOK_URL=https://swiftlinkng.com/autosync-webhook
```

The token and base URL fall back to `autoSyncToken` and `autoSyncUrl` if the
SMS-specific variables are absent. The webhook URL defaults to
`APP_URL/autosync-webhook`. Use an AutoSync-approved sender ID. After changing
configuration, rebuild Laravel's configuration cache (`php artisan config:cache`).

Requests support 1–1000 Nigerian numbers, a message up to 1000 characters and
a sender ID up to 20 characters. Validation runs before wallet debit. Each
bulk request uses the order reference as AutoSync's `request_ref` idempotency
key. Confirmed failures use the existing wallet refund flow; pending, timeout,
server-error and unrecognized responses remain pending. The existing
`AutoSyncWebhookJob` handles final successful/failed callbacks; ensure the
webhook is reachable and the queue worker is running. If no callback arrives,
reconcile the pending order with AutoSync before refunding or resending.

Provider documentation: [SMS options](https://autosyncng.com/user/developer/docs?path=v1%2Fsms%2Fget.json),
[send SMS](https://autosyncng.com/user/developer/docs?path=v1%2Fsms%2Fpost.json),
[authentication](https://autosyncng.com/user/developer/docs?path=v1%2Fmisc%2Fauth.json).

Run the HTTP-faked integration tests with
`php vendor/bin/phpunit tests/Feature/AutoSyncSMSServiceTest.php`.
The application's test bootstrap requires its configured database; these tests
do not send live SMS.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## International airtime and data

Run `php artisan migrate --force` during deployment and rebuild the web assets with `npm run build`. International is available at `/dashboard/international` and in the Flutter dashboard. Admins configure `/dashboard/international-admin`: VTUgate API key (stored encrypted; blank keeps the existing key), availability, a shared multiplier (default `1.03`), and country calling-code overrides keyed by two-letter ISO country codes. An optional `VTUGATE_API_KEY` environment variable supplies the fallback credential. No credentials are sent to the clients.

Prices use VTUgate international `fxrate.charged_to_user` in NGN, multiplied and rounded up to the nearest kobo. The selected amount is in the recipient's currency; local fixed denominations and local limits are used, never USD sender denominations. Quotes last two minutes. Before reserving wallet funds, a fresh provider preview must match the confirmed amount. Changed rates require confirmation of a new quote.

Purchases require the existing transaction PIN and reserve the wallet once per quote. VTUgate's documented top-up request does not support a client idempotency reference: ambiguous submissions remain processing and are never automatically resent or refunded. Known provider transaction IDs can be checked in customer history. Unknown outcomes require provider confirmation and an audited admin resolution after five minutes. Include pending international transactions in operational reconciliation.

### Sochitel route

The supplied **ARTX API Specification 5.9.6 (build 668, API 20260611)** uses JSON commands posted to one account-assigned HTTPS URL. Staging and production use this same request format and authentication with different URLs and credentials. Run migrations including `2026_10_19_000001_add_sochitel_artx_protocol`, then deploy the backend and web assets. Existing configured RSA accounts remain explicitly on **REST** to preserve pending transactions; new settings default to **ARTX**. Do not change protocols until existing Sochitel transactions are resolved.

For ARTX production, use `SOCHITEL_ARTX_URL=https://artxh1.sochitel.com/apiv8` (also the configuration default) and refresh Laravel's config cache. The privately supplied credentials are for production. Staging uses `https://clientportal.sochitel.com/api` with separate staging credentials. Use the PDF for request structures and authentication in both environments; select the production URL for the production credentials. If the account requires mutual TLS (status 73), configure `SOCHITEL_ARTX_CERTIFICATE` and `SOCHITEL_ARTX_SSL_KEY` with server certificate/key paths. HTTPS certificate verification stays enabled and redirects are disabled. In `/dashboard/international-admin`, select ARTX and save its username, password, account currency and NGN conversion while international top-ups are disabled or VTUgate is selected. Passwords are encrypted at rest and omitted from responses/audits. Test the saved connection before activation. The read-only test uses `getBalance`, `getOperators`, and `getOperatorProducts`; no purchase is submitted.

ARTX authentication uses a unique 40-character salt, `SHA512(salt + SHA1(password))`, and the optional payload signature in the order specified on page 7. Catalogues normalize keyed operator/product objects, fixed/range prices and both currencies. International airtime/data screens accept phone products; Internet accepts category 6 Internet services with type 3 bill accounts or type 4 mobile data. Products requiring uncollected mandatory extra parameters are excluded. Provider account cost × NGN conversion × markup determines customer prices. Fixed packages retain their provider IDs and names. Variable ranges require proportional price endpoints; unsupported pricing is excluded.

Purchases use `execTransaction`, explicit `productId`, `amountOperator` (local currency), `simulate:0` and an alphanumeric `userReference` of at most 30 characters. Type 3 sends the entered `accountId` unchanged; mobile products send international `msisdn`. Reconciliation uses `getTransaction` by `userReference`, its nested transaction status and recipient extra parameters. The top-level call reference is never used as the purchase reference. Returned customer instructions are stored in the order receipt and included in successful Internet purchase messages. Pending statuses 9, 46 and 59, unknown/mismatched responses, missing lookups and ambiguous failures remain pending without resending or refunding. Confirmed transaction failures refund once. Run the scheduler/queue for reconciliation. Account/protocol changes are blocked while either Internet or International Sochitel transactions remain pending; server endpoint changes also invalidate account matching.

The **REST / RSA** option retains the previous integration for accounts provisioned for that separate API: `SOCHITEL_BASE_URL`, numeric user ID, encrypted 4096-bit RSA private key and matching registered certificate. Its requests and pending transactions retain their original protocol. Do not use these credentials for ARTX.

Verification uses mocked provider responses; no live ARTX connection or paid purchase has been verified. Run `php vendor/bin/phpunit tests/Feature/SochitelArtxTest.php tests/Feature/SochitelApiTest.php` and the Internet/admin regression tests, plus `npm run build`. The ARTX tests use the supplied PDF's command, authentication, catalogue, purchase and lookup shapes.

### Internet service

Run `php artisan migrate --force`, rebuild web assets with `npm run build`, and release the Flutter app. Internet is available at `/dashboard/internet` and in both service menus. The migration adds an Internet category and Smile, Spectranet and Nitel subcategories. Their existing admin status switches control availability.

Smile loads current `smile-direct` packages and purchases through the existing VTpass credentials. Spectranet and Nitel/ntel use the Nigeria Internet-services catalogue (legacy REST: fixed data) from the Sochitel credentials in International admin, independently of the International provider switch. Sochitel Internet pricing uses that account's NGN conversion and multiplier; Smile uses the current VTpass package amount rounded up to whole naira. Missing provider packages remain unavailable rather than using sample prices. Existing AutoSync `AUTOSYNCAWUF` plans named Fibre X/FiberX are surfaced without changing their provider configuration or purchase route.

Internet purchases require the transaction PIN and validate the selected package and amount on the server. New provider purchases debit and create the order atomically, retain the request key for safe retries, and keep timeouts pending. The minute scheduler runs `internet:reconcile` to query the original provider, match the transaction and settle or refund once. Do not resend pending purchases. The normal Transactions screen contains the orders.

OPay: a live creation test with the requested 33-character email on 18 October 2026 was rejected with `C_1113` (request IP not whitelisted), before email-length behavior could be verified. No successful account creation was reported. Valid emails are never truncated. Only an explicit provider email-length rejection retries once using the same reference without the optional email; timeouts and IP errors never trigger this retry. Both clients display an alternative-bank note when OPay creation fails. Repeat the email test from the whitelisted server before attributing this user's issue to email length.


### Internet admin controls

Run the additional `2026_10_18_000002_add_internet_admin_controls` migration and rebuild the web assets. Admin → **Internet Settings** (`/dashboard/internet-admin`) now manages all four providers. Its API routes require an authenticated administrator and record setting changes, package syncs and connection-test results in `service_control_audits`.

- **Sync packages** fetches the current Smile/Sochitel catalogue. Fibre X imports the existing AutoSync product configuration without replacing its purchase integration. Package switches and overrides survive renames and subsequent syncs; removed packages become unavailable, while failed syncs preserve the last list. After the first sync, newly introduced provider packages require another sync before sale.
- **Provider pricing** accepts an optional multiplier and an additional NGN fee. A blank multiplier retains the original pricing (including Fibre X customer tiers). An explicit multiplier uses provider cost; when an AutoSync package has no cost, it uses its existing tier amount. Final charges round up to whole naira. Individual package overrides replace that formula. Packages priced below a known provider cost are blocked until corrected.
- **Availability** is enforced for both clients on the server, including Fibre X packages reached through the original Data page. Purchases recheck current controls before debiting, while already pending purchases retain their original amount.
- **Test connection** makes read-only requests: VTpass Smile catalogue, Sochitel account/currency and compatible catalogue, or AutoSync's documented `GET /me`. These checks do not submit purchases or prove that the provider's purchase permission is enabled. Existing provider credentials remain in their established configuration locations; secrets are never returned by the Internet admin endpoints.

Verification: `vendor/bin/phpunit tests/Feature/InternetAdminTest.php`, the existing Internet/Sochitel/OPay/cable regression suites, and `npm run build`. Provider calls in tests are mocked; no paid provider transactions are used for admin testing.
