#!/bin/bash
# Синхронизирует рантайм сервиса на dev-VPS с этим репозиторием.
# Рантайм держим отдельно от чекаута, чтобы переключение ветки не роняло сервис.
set -euo pipefail
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEST="${HW_PHOTOS_HOME:-/home/dev/hw-photos}"

mkdir -p "$DEST/test"
cp "$SRC"/{server.js,package.json,package-lock.json,README.md,backup.sh,.gitignore} "$DEST"/
cp "$SRC"/test/smoke.mjs "$DEST"/test/
chmod +x "$DEST/backup.sh"

if [ ! -d "$DEST/node_modules" ]; then
  (cd "$DEST" && npm install --omit=dev)
fi

if ! diff -q "$SRC/hw-photos.service" /etc/systemd/system/hw-photos.service >/dev/null 2>&1; then
  sudo cp "$SRC/hw-photos.service" /etc/systemd/system/hw-photos.service
  sudo systemctl daemon-reload
fi

sudo systemctl restart hw-photos
sleep 2
curl -fsS http://127.0.0.1:"${HW_PHOTOS_PORT:-4320}"/healthz && echo
(cd "$DEST" && node test/smoke.mjs)
