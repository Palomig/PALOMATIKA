#!/usr/bin/env bash
set -euo pipefail

source /etc/mtproxy-bot/env

args=(
  -u mtproxybot
  -p "${STATS_PORT}"
  -H "${PORT}"
  -S "${SECRET}"
  --aes-pwd /etc/mtproxy-bot/proxy-secret
  /etc/mtproxy-bot/proxy-multi.conf
  -M 1
)

if [[ -n "${TAG:-}" ]]; then
  args+=(-P "${TAG}")
fi

exec /opt/mtproxy-bot/objs/bin/mtproto-proxy "${args[@]}"
