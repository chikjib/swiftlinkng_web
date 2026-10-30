# Sochitel configuration

The developer portal documents the REST API:
https://clientportal.sochitel.com/apidoc.html?section=getting-started

The client portal REST base URL `https://clientportal.sochitel.com/api` was verified with authenticated read-only balance and operator requests on 2026-10-30. Its deployed response format is `{"sysErr":0,"sysId":"...","response":...}`, rather than the top-level `errno` format shown in the public documentation. Balance is an object inside `response`; the operator catalogue is a list directly inside `response`. Both REST formats are supported. Keep this URL for the matching client portal account; production access can have a different assigned base URL and credentials.

## REST / RSA setup

1. Obtain the **REST base URL** and numeric **account User ID** from Sochitel. Set `SOCHITEL_BASE_URL` on the backend server to that base URL. Do not use the documentation URL or an ARTX command URL. The application appends `/balance`, `/operators` and `/transaction`.
2. Generate a 4096-bit RSA key pair on a trusted machine:

   ```sh
   openssl genrsa -out sochitel-private.pem 4096
   openssl rsa -in sochitel-private.pem -pubout -out sochitel-public.pem
   ```

3. Register the public key in the Sochitel portal's API/RSA configuration. If the account restricts source IPs, allow the backend server's outbound public IP.
4. In **International Settings**, select **REST / RSA**, enter the numeric User ID and private key, and set the exact account currency. Set the NGN conversion rate if the currency is not NGN. Keep the private key secret; admin stores it encrypted. Configure a new account with top-ups disabled or VTUgate selected, then save.
5. After deploying the backend and web build and updating server environment values, run `php artisan config:cache` and `php artisan route:clear`. Restart long-running workers through the normal deployment process.
6. Click **Test Sochitel Connection**. This checks balance, currency and compatible products without purchasing anything. Enable the Sochitel route after it succeeds. In **Internet Settings**, test/sync Spectranet and Nitel and review package switches and prices.

Pending payments must be resolved before changing accounts or protocols. Changing the server's endpoint also changes account matching for pending payments.

## If the account only has an ARTX username/password

ARTX is a separate JSON-command API. Do not put its username/password into REST/RSA fields. Keep ARTX selected for that account, or obtain REST access from Sochitel.

The production ARTX handler checked on 2026-10-30 is `https://artxh1.sochitel.com/apiv8.php`. The old `/apiv8` path returned HTML HTTP 404 for GET and POST, causing the unreadable-response warnings. A credential-free read-only command to `/apiv8.php` returned a JSON protocol error, verifying the handler's existence but **not** account authentication. The default, example environment and local environment have been corrected to `.php`. Existing deployed `.env` values must also be updated and configuration cached again.

For an ARTX account requiring mutual TLS, set `SOCHITEL_ARTX_CERTIFICATE` and `SOCHITEL_ARTX_SSL_KEY` to its client certificate/key paths.

## Airtime pricing and empty Internet packages

Airtime minimum and maximum denominations use the exact account-currency prices returned in the catalogue. Interior amounts use a proportional conversion only when the two endpoints agree within currency rounding. If they do not agree, an exact interior price cannot be inferred from this REST catalogue; obtain a supported amount-specific quote mechanism from Sochitel. Do not replace the check with an assumed fee curve. `sochitel.range_quote_unavailable` logs the product and endpoint prices needed to investigate.

ARTX Internet catalogue requests fetch all Nigerian categories, then accept fixed bill-payment bundles in category 6.x and fixed mobile-data bundles in categories 4.x/6.x. Filtering the upstream request to category 6.0 hides providers whose bundles use category 4.x. Variable-amount products and products requiring additional mandatory fields still need a separate purchase flow and are excluded.

An empty Spectranet or Nitel list does not establish whether Sochitel offers that brand. Check the authenticated account's operators/products, then Internet admin provider enablement, package availability and the latest package sync. Account access determines the actual catalogue; public API documentation does not promise those brands. The local `swiftlink_livedata` database checked on 2026-10-30 has no `international_settings` table, so it cannot verify a deployed account's packages.

## Errors and rate limits

The connection test now has its own limit of five tests per minute. Loading settings, saving settings and Internet admin actions no longer consume that test counter. An actual limit response includes `Retry-After` and a readable retry message. General API and admin limits still apply.

Connection failures show safe HTTP/provider codes and guidance: invalid endpoint, RSA key/user ID, blocked source IP or server clock. Raw provider bodies, credentials and signatures are not exposed. Transaction ambiguity still remains pending for reconciliation, without automatic resubmission.

## Operator logos

The linked public REST documentation does not list a logo field or logo endpoint. The integration accepts optional HTTPS `logo`, `logoUrl` or `logoUrls` metadata if an account returns it. Admin operator-ID overrides take precedence. Recognized Nigerian brands use bundled logos when remote images are absent or fail; no Nigerian logo is assigned to an operator in another country.

Logos are included in international operator responses and Internet catalogue/provider plans. The website uses those URLs with bundled fallbacks. Other operators need an admin HTTPS override unless Sochitel provides additional logo metadata. No logo API endpoint or ID-to-image mapping has been assumed.

## Verification

```sh
php vendor/bin/phpunit tests/Feature/SochitelApiTest.php tests/Feature/SochitelArtxTest.php tests/Feature/InternetServiceTest.php tests/Feature/InternetAdminTest.php
npm run build
```

Provider requests in regression tests are mocked. A successful credentialed live connection and a paid transaction must be verified separately after configuring the account.
