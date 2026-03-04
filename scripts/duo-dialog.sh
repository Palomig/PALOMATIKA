#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   ./scripts/duo-dialog.sh "Topic" [rounds] [outdir]

TOPIC="${1:?Topic is required}"
MAX_ROUNDS="${2:-5}"
OUTDIR="${3:-/home/dev/palomatika/duo-dialog-$(date -u +%Y%m%dT%H%M%SZ)}"

TIMEOUT_CLAUDE="${TIMEOUT_CLAUDE:-120}"
TIMEOUT_CODEX="${TIMEOUT_CODEX:-180}"
MAX_RETRIES="${MAX_RETRIES:-3}"
MIN_RESPONSE_LEN="${MIN_RESPONSE_LEN:-20}"
BACKOFF_BASE="${BACKOFF_BASE:-4}"

mkdir -p "$OUTDIR"
LOG="$OUTDIR/shared_log.md"
STATE="$OUTDIR/state.json"
REPORT="$OUTDIR/report.md"

cat > "$LOG" <<EOF
# Codex ↔ Claude Dialog

Topic: $TOPIC
Started: $(date -u +"%Y-%m-%dT%H:%M:%SZ")
Rounds: $MAX_ROUNDS

---
EOF

update_state() {
  local round="$1" status="$2" errors="$3"
  cat > "$STATE" <<EOF
{"round":$round,"status":"$status","errors":$errors,"updated":"$(date -u +"%Y-%m-%dT%H:%M:%SZ")"}
EOF
}

backoff_sleep() {
  local attempt="$1"
  sleep $(( BACKOFF_BASE * attempt ))
}

normalize_text() {
  sed 's/\x1b\[[0-9;]*m//g' | tr -d '\r'
}

call_claude() {
  local prompt="$1"
  local attempt=1
  while (( attempt <= MAX_RETRIES )); do
    local raw
    set +e
    raw=$(timeout "${TIMEOUT_CLAUDE}s" bash -lc "claude -p --permission-mode default \"\$0\" 2>&1" "$prompt")
    local code=$?
    set -e

    if (( code == 124 )); then
      backoff_sleep "$attempt"
      ((attempt++))
      continue
    fi

    local cleaned
    cleaned=$(printf '%s' "$raw" | normalize_text)
    local compact
    compact=$(printf '%s' "$cleaned" | tr -d '[:space:]')
    if (( ${#compact} >= MIN_RESPONSE_LEN )); then
      printf '%s' "$cleaned"
      return 0
    fi

    backoff_sleep "$attempt"
    ((attempt++))
  done

  printf '__EMPTY_RESPONSE__'
  return 1
}

call_codex() {
  local prompt="$1"
  local attempt=1
  while (( attempt <= MAX_RETRIES )); do
    local raw
    set +e
    raw=$(timeout "${TIMEOUT_CODEX}s" bash -lc "codex exec \"\$0\" 2>&1" "$prompt")
    local code=$?
    set -e

    if (( code == 124 )); then
      backoff_sleep "$attempt"
      ((attempt++))
      continue
    fi

    local cleaned
    cleaned=$(printf '%s' "$raw" | normalize_text)
    local compact
    compact=$(printf '%s' "$cleaned" | tr -d '[:space:]')
    if (( ${#compact} >= MIN_RESPONSE_LEN )); then
      printf '%s' "$cleaned"
      return 0
    fi

    backoff_sleep "$attempt"
    ((attempt++))
  done

  printf '__EMPTY_RESPONSE__'
  return 1
}

log_turn() {
  local round="$1" speaker="$2" content="$3"
  cat >> "$LOG" <<EOF

## Round $round — $speaker

$content

---
EOF
}

errors=0
update_state 0 running 0

next_prompt="You are Claude. Start technical discussion with Codex. Topic: $TOPIC. Structure: observation, hypothesis, fix, verification. Keep concise and concrete."

for ((round=1; round<=MAX_ROUNDS; round++)); do
  update_state "$round" running "$errors"

  claude_resp=$(call_claude "$next_prompt" || true)
  if [[ "$claude_resp" == "__EMPTY_RESPONSE__" ]]; then
    ((errors++))
    log_turn "$round" "Claude" "_EMPTY RESPONSE after retries_"
    if (( errors >= 3 )); then
      update_state "$round" aborted "$errors"
      break
    fi
    continue
  fi
  log_turn "$round" "Claude" "$claude_resp"

  codex_prompt=$(cat <<EOF
You are Codex. Respond to Claude with improvements and critical checks.
Topic: $TOPIC
Claude said:
$claude_resp

Return: 1) what's right 2) what's risky 3) patch plan 4) rollback plan.
EOF
)

  codex_resp=$(call_codex "$codex_prompt" || true)
  if [[ "$codex_resp" == "__EMPTY_RESPONSE__" ]]; then
    ((errors++))
    log_turn "$round" "Codex" "_EMPTY RESPONSE after retries_"
    if (( errors >= 3 )); then
      update_state "$round" aborted "$errors"
      break
    fi
    next_prompt="Codex failed to respond. Continue the topic and propose concrete next actions."
    continue
  fi
  log_turn "$round" "Codex" "$codex_resp"

  next_prompt=$(cat <<EOF
Continue discussion with Codex.
Topic: $TOPIC
Codex said:
$codex_resp

Refine and converge to implementation steps.
EOF
)
done

summary_prompt=$(cat <<EOF
Create final summary from this dialog.
Provide:
- root cause candidates
- immediate hotfix
- long-term architecture
- migration steps
- verification checklist

Dialog:
$(cat "$LOG")
EOF
)

final_summary=$(call_claude "$summary_prompt" || true)
cat > "$REPORT" <<EOF
# Dialog Summary

$final_summary
EOF

update_state "$MAX_ROUNDS" finished "$errors"

echo "LOG: $LOG"
echo "REPORT: $REPORT"
echo "STATE: $STATE"