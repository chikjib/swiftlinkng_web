#!/usr/bin/env bash
set -euo pipefail

app_path=${1:?Pass the absolute application directory}
php_bin=${2:?Pass the absolute PHP 8.4 executable}
cd "$app_path"
test -f artisan
test -f .env
test -w storage
test -w bootstrap/cache
"$php_bin" -r 'if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 4) { fwrite(STDERR, "The deployment CLI must use PHP 8.4.\n"); exit(1); }'

# The database/upload backup and paused cron/workers are prerequisites confirmed
# by the operator. This private archive is an additional CODE rollback copy.
umask 077
backup_directory="$HOME/.swiftlink-deploy-backups"
mkdir -p "$backup_directory"
chmod 700 "$backup_directory"
archive="$backup_directory/code-$(date -u +%Y%m%dT%H%M%SZ).tar.gz"

# A retried upgrade may already have the new vendor directory but old provider
# manifests. Artisan cannot clear these because it fails while booting first.
# Remove only generated bootstrap files, never application cache or sessions.
"$php_bin" -r 'foreach (["config", "packages", "services", "routes-v7", "events"] as $name) { $path = "bootstrap/cache/$name.php"; if (is_file($path) && !unlink($path)) { exit(1); } }'

# The pre-rendered maintenance page works even while vendor is being replaced.
"$php_bin" artisan down --render="errors::503" --retry=60
tar -czf "$archive" \
  --exclude='./.git' --exclude='./node_modules' \
  --exclude='./storage/app' --exclude='./storage/logs' \
  --exclude='./storage/framework' --exclude='./public/storage' \
  --exclude='./bootstrap/cache/*.php' .
printf '%s\n' "$archive" > "$backup_directory/latest-code-backup"
printf 'Code rollback archive saved privately at %s\n' "$archive"
