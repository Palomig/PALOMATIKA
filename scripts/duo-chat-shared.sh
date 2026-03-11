#!/usr/bin/env bash
set -euo pipefail
TOPIC="${1:?topic required}"
ROUNDS="${2:-6}"
SHARED_FILE="${3:-/home/dev/palomatika/DUO_SHARED_AUTH.md}"

mkdir -p "$(dirname "$SHARED_FILE")"
cat > "$SHARED_FILE" <<EOF
# Shared Dialog: Codex ↔ Claude

Topic: $TOPIC

Rules:
- Read full file before replying.
- Add concrete, technical analysis only.
- Focus on root cause, instrumentation, and production-safe fixes.

---
EOF

for i in $(seq 1 "$ROUNDS"); do
  codex_prompt=$(cat <<EOF
You are Codex in a paired engineering dialog.
Read this shared file first: $SHARED_FILE
Then produce Round $i analysis for the topic.
Required structure:
1) observation
2) hypothesis
3) concrete code change
4) verification step
Keep it specific to Telegram Mini App auth issues (E_COOKIE_SESSION).
EOF
)
  cdx_out=$(codex exec "$codex_prompt" 2>/dev/null || true)
  {
    echo ""
    echo "## Round $i — Codex"
    echo ""
    echo "$cdx_out"
    echo ""
  } >> "$SHARED_FILE"

  claude_prompt=$(cat <<EOF
You are Claude in a paired engineering dialog.
Read this shared file first: $SHARED_FILE
Then produce Round $i response that critiques Codex and improves the plan.
Required structure:
1) what is correct
2) what is risky
3) better fix
4) rollout/rollback plan
Keep it specific to Telegram Mini App auth issues (E_COOKIE_SESSION).
EOF
)
  cla_out=$(claude -p "$claude_prompt" --permission-mode default 2>/dev/null || true)
  {
    echo ""
    echo "## Round $i — Claude"
    echo ""
    echo "$cla_out"
    echo ""
  } >> "$SHARED_FILE"
done

echo "$SHARED_FILE"
