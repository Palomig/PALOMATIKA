#!/usr/bin/env bash
set -euo pipefail
TOPIC="$1"
OUT="${2:-/home/dev/palomatika/MODEL_DIALOG_AUTH_$(date -u +%Y%m%dT%H%M%SZ).md}"
ROUNDS="${3:-3}"

printf "# Model Dialog (Codex ↔ Claude)

Topic: %s

" "$TOPIC" > "$OUT"
msg="$TOPIC"
for i in $(seq 1 "$ROUNDS"); do
  echo "Running Codex round $i..." >&2
  cdx=$(codex exec "$msg" 2>/dev/null || true)
  printf "## Round %s — Codex

%s

" "$i" "$cdx" >> "$OUT"

  echo "Running Claude round $i..." >&2
  cla=$(claude -p "$cdx

Respond critically, propose concrete fixes, and next step." --permission-mode default 2>/dev/null || true)
  printf "## Round %s — Claude

%s

" "$i" "$cla" >> "$OUT"

  msg="$cla"
done

echo "$OUT"
