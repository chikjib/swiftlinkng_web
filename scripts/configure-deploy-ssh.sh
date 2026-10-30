#!/usr/bin/env bash
set -euo pipefail

ssh_directory=${1:-"$HOME/.ssh"}
: "${CPANEL_HOST:?Set the SSH hostname, without a URL scheme or port}"
: "${CPANEL_PORT:?Set the SSH port}"
: "${CPANEL_SSH_KEY:?Set the deployment private key}"
: "${CPANEL_KNOWN_HOSTS:?Set the independently verified server host key entry}"

if [[ ! "$CPANEL_PORT" =~ ^[0-9]{1,5}$ ]]; then
  echo '::error::CPANEL_PORT must be an SSH port number.' >&2
  exit 1
fi
port=$((10#$CPANEL_PORT))
if (( port < 1 || port > 65535 )); then
  echo '::error::CPANEL_PORT must be between 1 and 65535.' >&2
  exit 1
fi

install -d -m 700 "$ssh_directory"
printf '%s\n' "$CPANEL_SSH_KEY" | tr -d '\r' > "$ssh_directory/swiftlink_deploy"
chmod 600 "$ssh_directory/swiftlink_deploy"
printf '%s\n' "$CPANEL_KNOWN_HOSTS" | tr -d '\r' > "$ssh_directory/known_hosts"
chmod 600 "$ssh_directory/known_hosts"

lookup=$CPANEL_HOST
if (( port != 22 )); then lookup="[$CPANEL_HOST]:$port"; fi
if ! ssh-keygen -F "$lookup" -f "$ssh_directory/known_hosts" > /dev/null; then
  echo '::error::CPANEL_KNOWN_HOSTS has no entry matching CPANEL_HOST and CPANEL_PORT. Non-default SSH ports require [hostname]:port. Store the verified SERVER host key, not the deployment login key. See docs/LARAVEL_13_DEPLOYMENT.md.' >&2
  exit 1
fi
echo 'Matching known-host entry configured; strict server verification remains enabled.'
