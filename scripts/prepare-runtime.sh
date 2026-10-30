#!/usr/bin/env bash
set -euo pipefail

project_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$project_root"

# Git does not preserve empty directories. Prepare these before Composer
# scripts or Artisan boot, without modifying session data or signing keys.
for directory in \
  bootstrap/cache \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs; do
  mkdir -p "$directory"
  chmod u+rwx "$directory"
done
