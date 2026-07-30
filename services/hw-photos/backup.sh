#!/bin/bash
# Бэкап фото решений домашки: фото — единственный артефакт проверки ДЗ,
# терять его нельзя. Держим 7 последних суточных архивов.
set -euo pipefail
DATA="${HW_PHOTOS_DATA:-/home/dev/hw-photos-data}"
DEST="/home/dev/backups/hw-photos"
mkdir -p "$DEST"
tar -czf "$DEST/hw-photos-$(date +%F).tar.gz" -C "$(dirname "$DATA")" "$(basename "$DATA")"
ls -1t "$DEST"/hw-photos-*.tar.gz | tail -n +8 | xargs -r rm -f
