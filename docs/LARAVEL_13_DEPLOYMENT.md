# Laravel 13 / PHP 8.4 deployment

This release upgrades the existing Swiftlink application to Laravel 13.38.0,
Passport 13, Sanctum 4, Carbon 3, and PHPUnit 12. The lockfile is resolved for
PHP 8.4.0; development verification used PHP 8.4.20. Install the latest patched
PHP 8.4 release available from your host. Keep `composer.lock` in Git.

**A push now runs validation only. Production deployment is manual in GitHub
Actions. Do not trigger deployment until the staging rehearsal below passes.**
No production server, database, credentials, or backups were accessed or changed
while preparing this upgrade locally.

## What changes authentication

- The existing application layout, UUID OAuth client IDs, session cookie naming,
  Redis connection prefix, and file-session location are retained.
- `config/session.php` explicitly keeps PHP serialization. Switching to JSON
  during this deployment would invalidate existing sessions.
- The cache prefix includes the separator previously appended internally by
  Laravel 9. Existing object cache payloads remain supported.
- Passport registers its own routes now. The password grant remains enabled.
- Passport 13 requires client secrets to be hashed. The deployment runs
  `passport:hash --force` after code installation. Existing client IDs, signing
  keys, token rows, and user passwords are preserved. API clients continue
  sending their original secrets; do not replace those with database hashes.
- This repository already has the legacy Passport migrations, using UUIDs.
  Passport 13 supports that schema. No new schema migration is introduced here;
  the existing `oauth_personal_access_clients` table is kept for rollback.
- The app's existing token expiry policies are retained. Tokens that were already
  expired or revoked cannot be made valid by this deployment.

## 1. Rehearse on a separate staging site

1. Create a separate subdomain and application directory, with its own database,
   cache namespace, session cookie name, and storage. Keep staging isolated from
   live sessions and caches. Restore a suitably protected database copy there.
2. Give staging its own `.env`. Use sandbox provider credentials and controlled
   email/SMS delivery. Do not enable live payment, webhook, or recurring jobs.
   External integrations using raw cURL are not globally blocked by the test
   suite: the staging environment must isolate those integrations too.
3. Enable PHP **8.4 for both the subdomain and command line**. On standard cPanel,
   the CLI executable is often:

   ```bash
   /opt/cpanel/ea-php84/root/usr/bin/php -v
   ```

   CloudLinux/other hosts may use a different path. Ask your host if necessary.
   Confirm the domain's PHP version in MultiPHP Manager/PHP Selector as well;
   `php -v` alone only checks CLI.
4. Keep a pre-upgrade staging release available. Record `migrate:status` on the
   old release and review every pending migration. This upgrade does not require
   recreating any tables. Never use `migrate:fresh` on an existing database.
5. Test the exact commit and `composer.lock` intended for production. Install
   production dependencies with PHP 8.4:

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
   composer check-platform-reqs --no-dev
   npm ci --legacy-peer-deps
   npm run build
   ```

   Invoke Composer with PHP 8.4 if your default `php` uses another version.
   Never run `composer update` on the production server.
6. With staging cron/workers stopped, rehearse maintenance, the reviewed
   migrations, `passport:hash --force`, `upgrade:check`, config/view caching,
   and reopening. Use the same PHP 8.4 executable for every Artisan command.
7. Test all of these in a browser and the existing mobile app:
   - Existing login survives refresh, navigation, and browser restart as expected.
   - Existing mobile/API bearer token still works; a fresh login also works.
   - Logging out one device does not log out other devices or integrations.
   - Invalid passwords, password resets, and email verification behave correctly.
   - Wallet funding, purchases, reversals, balances, and transaction history agree.
   - Webhook signature verification, duplicate delivery, and retry handling work.
   - A sandbox queued job and scheduled task run with PHP 8.4.
   - Excel import/export, notifications, and admin screens still work.
8. Rehearse rollback on staging, including restoring the original OAuth client
   secrets. Passing local SQLite tests does not replace this MySQL/cPanel and
   provider integration rehearsal.

## 2. Before deploying production

- [ ] Identify and save the current production commit/release and PHP settings.
- [ ] Create a **database backup you have verified can be restored**. It must
      include OAuth clients/tokens and all financial tables.
- [ ] Back up `.env`, `storage/oauth-private.key`, `storage/oauth-public.key`,
      uploads, and any other server-only files. Keep backups private and off
      the public web root. If keys come from environment values, preserve those.
- [ ] Check available disk space for both the new vendor files and a rollback
      archive. The workflow saves an additional private CODE archive under
      `$HOME/.swiftlink-deploy-backups/`; this is not a database/upload backup.
- [ ] Keep the live `APP_KEY`, Passport keypair, all existing client IDs/secrets,
      session driver/domain/cookie/lifetime, Redis database and namespace.
      Do not generate new keys, create replacement OAuth clients, flush Redis,
      delete sessions, or run `optimize:clear` as part of this release.
- [ ] Set `APP_DEBUG=false`. Confirm `.env` still points to the intended live DB.
- [ ] Enable PHP 8.4 for the live domain. Ensure cron and Supervisor/queue workers
      also use the explicit PHP 8.4 executable. Keep the old PHP executable
      available for rollback if the previous release needs it.
- [ ] Enable required extensions for both CLI and web: mbstring, bcmath, curl,
      dom/XML, fileinfo, gd, intl, openssl, PDO/MySQL, sodium, tokenizer, and zip.
      Keep the existing Redis client/extension if Redis is used.
- [ ] Verify the exact `CPANEL_PHP` executable in the Laravel directory. Enabling
      modules for a different PHP installation or only the web handler does not
      enable them for this CLI. The workflow now streams the NEW production
      dependencies' standalone Composer platform check to that executable
      before entering maintenance or uploading any application files.
- [ ] Ensure the PHP web process can read/write existing session files and
      `storage`/`bootstrap/cache`. Check ownership; do not use `chmod 777`.
      Keep Passport private keys readable only by the application account
      (typically mode 600), and public keys 660 or 600.
- [ ] Stop scheduled jobs and drain/stop queue workers. Merely calling
      `queue:restart` is insufficient if Supervisor immediately starts them
      again. Account for webhooks retrying during the maintenance window.
- [ ] Review pending migrations against the production backup and staging result.
      The workflow runs `migrate --force`; any previously pending application
      migration will therefore run too.

## 3. Configure GitHub once

Existing Actions secrets are reused:

| Setting | Type | Purpose |
| --- | --- | --- |
| `CPANEL_HOST` | Secret | SSH hostname |
| `CPANEL_USER` | Secret | SSH account |
| `CPANEL_PORT` | Secret | SSH port |
| `CPANEL_PATH` | Secret | Absolute Laravel application directory containing `artisan` |
| `CPANEL_SSH_KEY` | Secret | Deployment private key |
| `CPANEL_KNOWN_HOSTS` | Secret | Verified SSH host key entry, including port if nonstandard |
| `CPANEL_PHP` | Variable | Absolute PHP 8.4 executable; defaults to `/opt/cpanel/ea-php84/root/usr/bin/php` |

Obtain/verify the SSH host fingerprint through cPanel or your hosting provider.
The workflow deliberately does not trust a freshly scanned host key.

If Actions reports `No ED25519 host key is known for [host]:port`, the saved
`CPANEL_KNOWN_HOSTS` entry does not match that exact SSH endpoint. On a trusted
computer, substitute the SAME hostname and port from your GitHub secrets:

```bash
ssh-keyscan -p YOUR_SSH_PORT -t ed25519 YOUR_SSH_HOST > cpanel-known-hosts.txt
ssh-keygen -lf cpanel-known-hosts.txt
```

Compare the displayed SHA256 fingerprint with the server's ED25519 fingerprint
provided through your host's trusted control panel/support channel. A scan alone
does not establish the server's identity. After it matches, replace the GitHub
Actions secret `CPANEL_KNOWN_HOSTS` with the full contents of the file, not just
the fingerprint. For a non-default port its line begins `[hostname]:port`; for
port 22 it normally begins `hostname`. Use the SSH server hostname, not an HTTPS
URL. The server host key differs from your deployment key in `CPANEL_SSH_KEY`.
Update the secret in the `production` environment if that environment defines
an overriding value. Rerun the workflow after saving the corrected secret.

Retain existing frontend `MIX_*` variables/public payment key used by your build.
The workflow uses the GitHub `production` environment; configure any desired
reviewers and branch restrictions there. Make deployment secrets available to
that environment or at repository scope.

The domain's document root/rewrites must continue pointing at Laravel's `public`
entry point. `CPANEL_PATH` is the application directory, not necessarily that
public document root. Do not move the application during this upgrade.

## 4. Push and deploy

1. Commit all changes, including `composer.json`, `composer.lock`, `.env.testing`,
   `phpunit.xml`, tests, scripts, workflows, and this guide. `.env.testing`
   contains dummy test values, not production credentials. Never commit `.env`
   or real signing keys.
2. Push to GitHub. Wait for **Validate PHP 8.4 upgrade** to pass.
3. After the prerequisites above, open **Actions → Deploy Swiftlink to cPanel →
   Run workflow**. Select the tested branch/commit and confirm the staging,
   backup, migration-review, and paused-job prerequisites.
4. Validation runs again. Production dependencies and frontend assets are built
   on GitHub with PHP 8.4 and Node 22.
5. The server preparation script checks the CLI, pre-renders maintenance mode,
   and saves the private code archive. Upload preserves all of `storage/`,
   `.env*`, local PHP configuration, and generated bootstrap caches. The vendor
   directory is synchronized exactly while maintenance is active.
6. The finish script removes old generated manifests, discovers packages, runs
   reviewed migrations, hashes existing OAuth client secrets, and runs the
   read-only `upgrade:check`. It then caches configuration/views, signals queue
   restart, and reopens the site. Failure leaves maintenance enabled; inspect
   the failed step rather than blindly running `artisan up`.
7. Immediately test existing and fresh browser/mobile logins, a controlled
   payment flow, balances, and webhook processing. Resume PHP 8.4 cron/workers
   only after these checks pass. Check Laravel and PHP-FPM logs without exposing
   credentials. Reload PHP-FPM/OPcache through cPanel if the host needs it.

If deploying manually over SSH instead, enter maintenance using the OLD release
before replacing code, preserve the same files, and perform the commands in
`scripts/deploy-finish.sh` only after the new locked vendor directory is installed.
Never execute the preparation/finish scripts against your local development app.

## 5. Rollback

If Artisan reports `Fruitcake\Cors\CorsServiceProvider` missing after a partial
upgrade, clear generated bootstrap manifests without booting Laravel. From the
Laravel directory, using the configured PHP executable:

```bash
/usr/local/bin/ea-php84 -r 'foreach (["config", "packages", "services", "routes-v7", "events"] as $name) { $path = "bootstrap/cache/$name.php"; if (is_file($path) && !unlink($path)) { exit(1); } }'
bash scripts/deploy-finish.sh "$PWD" /usr/local/bin/ea-php84
```

Run the finish command only if the previous upload completed and the application
is already in maintenance. Otherwise continue the normal workflow after clearing
the manifests. If the missing provider is explicitly listed in the server's
`config/app.php`, confirm the correct application directory and upgraded source
were uploaded. Do not reinstall the obsolete CORS package. The prepare script
now also clears bootstrap manifests before its first Artisan invocation.

If deployment stopped at the start of `deploy-finish.sh` with missing PHP
extensions, files have already been uploaded but that attempt has not yet run
its migrations or secret conversion. Install/enable the named extensions for
the exact PHP 8.4 CLI and web handler (hosting support may need to do this).
From the Laravel directory, verify that executable's modules and then resume
the finish script rather than simply running `artisan up`:

```bash
/usr/local/bin/ea-php84 -m
bash scripts/deploy-finish.sh "$PWD" /usr/local/bin/ea-php84
```

Use your configured `CPANEL_PHP` path if it differs. This resumes the remaining
checks/migrations/caching and reopens only after they pass. Retain the original
pre-upgrade backup: restarting the entire workflow may create another archive
of the partially deployed code. An SSH post-quantum warning is separate from
a PHP extension error.

### Existing referral rewards table

If migration `2026_08_02_000002_create_referral_rewards_table` is pending but
`referral_rewards` already exists from the July migration, do not drop the table
or manually insert a migration-history row. The corrected August migration
recognizes the existing ledger, checks its required columns and unique reward
key, widens the legacy fields, sets the commission default, and adds the combined
beneficiary/type index. Existing rows and their IDs remain in place. Wider
existing fields are retained. An unexpected schema stops with a review error.

Before retrying, take and verify a database backup, keep workers/cron stopped,
and keep maintenance enabled. Schema changes on a populated MySQL table may
take time and lock the table. Upload the corrected migration from this repository
without replacing `.env`, keys, or storage. Then, from the live Laravel folder:

```bash
bash scripts/deploy-finish.sh "$PWD" /usr/local/bin/ea-php84
/usr/local/bin/ea-php84 artisan migrate:status
```

The finish script records the migration on success and reopens the application
only after its remaining checks pass. This migration refuses automatic rollback
to protect the financial ledger; use a reviewed forward fix for schema recovery.
Editing it addresses installations where that migration is still pending;
installations that already recorded it will not rerun it.

Keep cron/workers stopped and the site in maintenance while deciding what to do.
Record the failed step and whether migrations or `passport:hash` ran.

- Restore the previous code **and matching vendor directory** from the private
  archive or a known-good release. Extract into a separate directory first and
  inspect it. Do not overwrite uploads/session storage with old copies.
- Restore the previous CLI/web PHP selection if required by that release.
- **Code rollback alone is not enough after client-secret hashing.** The old
  Passport version expects its original secret representation. Restore original
  `oauth_clients.secret` values from the verified backup, matched by client ID,
  under a reviewed recovery procedure. Keep IDs, tokens, and signing keys intact.
- Review the actual migrations that ran. Do not blindly run `migrate:rollback`:
  an older pending migration batch may include important business schema.
- A full pre-upgrade DB restore is appropriate only if all writes stayed stopped
  and no new live transactions occurred. **After reopening, a stale full DB
  restore can erase real payments.** Prefer a targeted authentication/schema
  recovery or forward fix, with reconciliation of any intervening writes.
- Rebuild generated manifests/config/views using the restored release. Verify
  browser/API authentication before `artisan up`, then resume workers/cron.

## Local validation

Verified locally on 21 October 2026: 187 PHP tests / 907 assertions, 19 JavaScript
tests, production frontend build, Blade compilation, 323 registered routes,
PHP platform checks, and dependency audit passed. Server-specific behavior and
real provider integration still require the staging rehearsal above.

Run with PHP 8.4:

```bash
composer validate --strict
composer check-platform-reqs
composer audit
vendor/bin/phpunit --fail-on-warning --fail-on-risky --fail-on-phpunit-notice
node --test tests/js/*.test.cjs
npm run build
```

The PHP suite uses SQLite memory databases, dummy environment values, generated
in-memory signing keys, and provider fakes. It includes legacy file-session and
RS256 token compatibility, new login/device logout, repeated secret conversion,
and readiness-command checks. No live provider transactions were exercised.

Official references: [Laravel 10 upgrade](https://laravel.com/docs/10.x/upgrade),
[Laravel 11 upgrade](https://laravel.com/docs/11.x/upgrade),
[Laravel 12 upgrade](https://laravel.com/docs/12.x/upgrade),
[Laravel 13 upgrade](https://laravel.com/docs/13.x/upgrade),
[Passport upgrade](https://github.com/laravel/passport/blob/13.x/UPGRADE.md).
