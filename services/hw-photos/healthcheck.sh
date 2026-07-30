#!/bin/bash
# Сторож hw-photos: сервис хранит фото решений домашки, и если он лёг, сдача ДЗ
# продолжает работать (уходит на фолбэк), но новые фото копятся на хостинге —
# узнавать об этом надо сразу, а не случайно.
#
# Сначала пробуем поднять сам, в телегу пишем только при СМЕНЕ состояния,
# иначе за ночь набежит полсотни одинаковых сообщений.
set -uo pipefail

URL="${HW_PHOTOS_HEALTH_URL:-https://palomig.ru/hw-photos/healthz}"
STATE_FILE="${HW_PHOTOS_STATE:-/home/dev/hw-photos/.health-state}"
CHAT_ID="${HW_PHOTOS_CHAT_ID:-245710727}"
TOKEN=$(python3 -c "import json;print(json.load(open('/home/dev/.openclaw/openclaw.json'))['channels']['telegram']['botToken'])" 2>/dev/null)

notify() {
    [ -n "${TOKEN:-}" ] || return 0
    curl -s -m 20 -o /dev/null \
        --data-urlencode "chat_id=${CHAT_ID}" \
        --data-urlencode "text=$1" \
        --data-urlencode "parse_mode=HTML" \
        "https://api.telegram.org/bot${TOKEN}/sendMessage"
}

probe() {
    curl -fsS -m 15 "$URL" 2>/dev/null | grep -q '"ok":true'
}

previous=$(cat "$STATE_FILE" 2>/dev/null || echo "up")

if probe; then
    if [ "$previous" != "up" ]; then
        notify "✅ <b>hw-photos</b> снова отвечает. Фото домашки опять уезжают на VPS."
    fi
    echo "up" > "$STATE_FILE"
    exit 0
fi

# Первая попытка не удалась — перезапускаем и даём подняться.
sudo -n systemctl restart hw-photos 2>/dev/null
sleep 5

if probe; then
    if [ "$previous" != "up" ]; then
        notify "✅ <b>hw-photos</b> снова отвечает (после перезапуска)."
    else
        notify "⚠️ <b>hw-photos</b> не отвечал, перезапустил — сейчас в порядке."
    fi
    echo "up" > "$STATE_FILE"
    exit 0
fi

if [ "$previous" != "down" ]; then
    notify "🔴 <b>hw-photos лежит</b> и не поднялся перезапуском.
Фото решений домашки временно сохраняются на хостинге (фолбэк), сдача ДЗ работает.
service: $(systemctl is-active hw-photos 2>/dev/null), url: ${URL}"
fi
echo "down" > "$STATE_FILE"
exit 1
