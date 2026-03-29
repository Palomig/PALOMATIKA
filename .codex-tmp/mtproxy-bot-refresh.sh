#!/usr/bin/env bash
set -euo pipefail

install -d -m 755 /etc/mtproxy-bot
curl -fsSL https://core.telegram.org/getProxySecret -o /etc/mtproxy-bot/proxy-secret
curl -fsSL https://core.telegram.org/getProxyConfig -o /etc/mtproxy-bot/proxy-multi.conf
chmod 600 /etc/mtproxy-bot/proxy-secret /etc/mtproxy-bot/proxy-multi.conf
chown root:root /etc/mtproxy-bot/proxy-secret /etc/mtproxy-bot/proxy-multi.conf
