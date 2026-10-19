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

The admin screen also supports switching between VTUgate and Sochitel for both clients. The default remains VTUgate. Run the `2026_10_17_000001` migration, deploy the updated backend/web assets, and release the updated Flutter app before activating Sochitel: its plans carry distinct product IDs, including bundles with the same local denomination. Existing VTUgate clients remain compatible while VTUgate is selected.

Set `SOCHITEL_BASE_URL` to the HTTPS API endpoint assigned to the account (including a base path if required), then refresh Laravel's config cache. The public `https://clientportal.sochitel.com/` is the credential/testing portal; the documentation uses an example API hostname. Do not substitute the portal URL without confirming the account's endpoint. Keep this server configuration trusted; signed requests never follow redirects.

In `/dashboard/international-admin`, save the Sochitel account user ID, an unencrypted 4096-bit RSA private key, account currency, and NGN per unit of account currency while VTUgate is selected or international top-ups are disabled. Upload the matching public key in the Sochitel portal. The private key is encrypted at rest and never returned or included in audit records. Click **Test Sochitel Connection**: it reads balance/currency and catalogue, and makes no purchase. After it succeeds, select **Sochitel** and save. A change of endpoint, user ID, key or currency requires a new verification. Switching back to VTUgate retains the saved Sochitel credentials for reconciliation.

Sochitel account cost × the editable NGN conversion × the shared multiplier determines customer pricing; NGN accounts always use conversion 1. Keep non-NGN exchange rates current. Fixed data products use their provider names and IDs. Airtime ranges are quoted from the catalogue's user/operator amount endpoints only when they agree on a proportional rate within a cent of rounding; inconsistent ranges are rejected. Sochitel has no documented preview/FX endpoint: account-specific catalogue/pricing must be checked on the actual account before launch. Products requiring additional mandatory fields, vouchers, bill payments and variable data ranges are excluded from these phone-only screens. The documented catalogue supplies no logos: configure HTTPS logo URLs by Sochitel operator ID in admin. Country names/calling codes ship locally with upstream notices in `resources/data`, with admin overrides preserved.

Quotes freeze the provider/account/product and expire after two minutes. A route/account/price change requires a new customer confirmation before debit. A purchase always reconciles with its original provider. Sochitel uses a unique alphanumeric reference of at most 30 characters; a timeout is checked using `GET /transaction/user/{reference}`, never by resending the top-up or moving it to another route. Pending statuses 9, 46 and 59 remain pending (59 requires provider support even though its appendix type says failure). Confirmed transaction failures and explicit pre-transaction request rejections refund the wallet once. Unknown/mismatched responses and missing lookups remain processing for provider investigation. Changing Sochitel account user ID is blocked while Sochitel payments are pending. Keep the scheduler and queue worker running for `international:reconcile`.

Verification: `php vendor/bin/phpunit --filter 'International|Sochitel'`; `node --test tests/js/international-preview.test.cjs`; `npm run build`; Flutter: `flutter test test/international_test.dart` and targeted `flutter analyze`. Tests use documented response fixtures and verify RSA signatures, both routes, named plans, pricing, route changes, timeout recovery, pending/manual outcomes and single refunds. No live Sochitel credentials or purchases were available for verification. The route stays inactive until the account's connection test passes.

Provider reference: https://clientportal.sochitel.com/apidoc.html?section=getting-started

### Internet service

Run `php artisan migrate --force`, rebuild web assets with `npm run build`, and release the Flutter app. Internet is available at `/dashboard/internet` and in both service menus. The migration adds an Internet category and Smile, Spectranet and Nitel subcategories. Their existing admin status switches control availability.

Smile loads current `smile-direct` packages and purchases through the existing VTpass credentials. Spectranet and Nitel/ntel use the Nigeria fixed-data catalogue from the Sochitel credentials in International admin, independently of the International provider switch. Sochitel Internet pricing uses that account's NGN conversion and multiplier; Smile uses the current VTpass package amount rounded up to whole naira. Missing provider packages remain unavailable rather than using sample prices. Existing AutoSync `AUTOSYNCAWUF` plans named Fibre X/FiberX are surfaced without changing their provider configuration or purchase route.

Internet purchases require the transaction PIN and validate the selected package and amount on the server. New provider purchases debit and create the order atomically, retain the request key for safe retries, and keep timeouts pending. The minute scheduler runs `internet:reconcile` to query the original provider, match the transaction and settle or refund once. Do not resend pending purchases. The normal Transactions screen contains the orders.

OPay: a live creation test with the requested 33-character email on 18 October 2026 was rejected with `C_1113` (request IP not whitelisted), before email-length behavior could be verified. No successful account creation was reported. Valid emails are never truncated. Only an explicit provider email-length rejection retries once using the same reference without the optional email; timeouts and IP errors never trigger this retry. Both clients display an alternative-bank note when OPay creation fails. Repeat the email test from the whitelisted server before attributing this user's issue to email length.


### Internet admin controls

Run the additional `2026_10_18_000002_add_internet_admin_controls` migration and rebuild the web assets. Admin → **Internet Settings** (`/dashboard/internet-admin`) now manages all four providers. Its API routes require an authenticated administrator and record setting changes, package syncs and connection-test results in `service_control_audits`.

- **Sync packages** fetches the current Smile/Sochitel catalogue. Fibre X imports the existing AutoSync product configuration without replacing its purchase integration. Package switches and overrides survive renames and subsequent syncs; removed packages become unavailable, while failed syncs preserve the last list. After the first sync, newly introduced provider packages require another sync before sale.
- **Provider pricing** accepts an optional multiplier and an additional NGN fee. A blank multiplier retains the original pricing (including Fibre X customer tiers). An explicit multiplier uses provider cost; when an AutoSync package has no cost, it uses its existing tier amount. Final charges round up to whole naira. Individual package overrides replace that formula. Packages priced below a known provider cost are blocked until corrected.
- **Availability** is enforced for both clients on the server, including Fibre X packages reached through the original Data page. Purchases recheck current controls before debiting, while already pending purchases retain their original amount.
- **Test connection** makes read-only requests: VTpass Smile catalogue, Sochitel account/currency and compatible catalogue, or AutoSync's documented `GET /me`. These checks do not submit purchases or prove that the provider's purchase permission is enabled. Existing provider credentials remain in their established configuration locations; secrets are never returned by the Internet admin endpoints.

Verification: `vendor/bin/phpunit tests/Feature/InternetAdminTest.php`, the existing Internet/Sochitel/OPay/cable regression suites, and `npm run build`. Provider calls in tests are mocked; no paid provider transactions are used for admin testing.
