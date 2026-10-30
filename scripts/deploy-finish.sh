#!/usr/bin/env bash
set -euo pipefail

app_path=${1:?Pass the absolute application directory}
php_bin=${2:?Pass the absolute PHP 8.4 executable}
cd "$app_path"
test -f storage/framework/down
"$php_bin" -r 'if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 4) { exit(1); } require "vendor/autoload.php";'

# Remove generated manifests from the old framework before Laravel boots.
# Do not clear the application cache or sessions.
"$php_bin" -r 'foreach (["config", "packages", "services", "routes-v7", "events"] as $name) { $path = "bootstrap/cache/$name.php"; if (is_file($path) && !unlink($path)) { exit(1); } }'
"$php_bin" artisan package:discover --ansi

# Review pending migrations in staging before approving the workflow.
"$php_bin" artisan migrate --force
# Passport 13 requires hashed secrets. This keeps client IDs and token rows.
# The pre-upgrade database backup is required to undo this on rollback.
"$php_bin" artisan passport:hash --force
"$php_bin" artisan upgrade:check
"$php_bin" artisan config:cache
"$php_bin" artisan view:cache
"$php_bin" artisan queue:restart
"$php_bin" artisan up
printf 'Deployment completed. Verify web/mobile login, payments, and webhook delivery; then resume cron/workers.\n'
