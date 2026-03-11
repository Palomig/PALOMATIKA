#!/usr/bin/env bash
set -u
set -o pipefail

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
RUNLOG="$OUTDIR/run.log"

exec > >(tee -a "$RUNLOG") 2>&1

echo "[duo] start $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
echo "[duo] outdir: $OUTDIR"

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

normalize_text() {
  sed 's/\x1b\[[0-9;]*m//g' | tr -d '\r'
}

check_nonempty() {
  local txt="$1"
  local compact
  compact=$(printf '%s' "$txt" | tr -d '[:space:]')
  [[ ${#compact} -ge $MIN_RESPONSE_LEN ]]
}

backoff_sleep() {
  local attempt="$1"
  local d=$(( BACKOFF_BASE * attempt ))
  echo "[duo] backoff ${d}s"
  sleep "$d"
}

preflight() {
  local ok=0
  command -v claude >/dev/null 2>&1 || { echo "[duo] ERROR: claude not found"; ok=1; }
  command -v codex  >/dev/null 2>&1 || { echo "[duo] ERROR: codex not found"; ok=1; }
  if [[ $ok -ne 0 ]]; then
    update_state 0 failed_preflight 1
    exit 1
  fi

  echo "[duo] preflight ok: binaries found"
}

call_claude() {
  local prompt="$1"
  local attempt=1
  while (( attempt <= MAX_RETRIES )); do
    echo "[duo] claude attempt $attempt/$MAX_RETRIES"
    local raw
    raw=$(timeout "${TIMEOUT_CLAUDE}s" bash -lc 'claude -p --permission-mode default "$1" 2>&1' _ "$prompt" || true)
    raw=$(printf '%s' "$raw" | normalize_text)
    if check_nonempty "$raw"; then
      printf '%s' "$raw"
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
    echo "[duo] codex attempt $attempt/$MAX_RETRIES"
    local raw
    raw=$(timeout "${TIMEOUT_CODEX}s" bash -lc 'codex exec "$1" 2>&1' _ "$prompt" || true)
    raw=$(printf '%s' "$raw" | normalize_text)
    if check_nonempty "$raw"; then
      printf '%s' "$raw"
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

preflight
errors=0
update_state 0 running 0

next_prompt="You are Claude. Start technical discussion with Codex. Topic: $TOPIC. Structure: observation, hypothesis, fix, verification."

for ((round=1; round<=MAX_ROUNDS; round++)); do
  update_state "$round" running "$errors"

  claude_resp=$(call_claude "$next_prompt" || true)
  if [[ "$claude_resp" == "__EMPTY_RESPONSE__" ]]; then
    ((errors++))
    log_turn "$round" "Claude" "_EMPTY RESPONSE after retries_"
    if (( errors >= 3 )); then update_state "$round" aborted "$errors"; break; fi
    continue
  fi
  log_turn "$round" "Claude" "$claude_resp"

  codex_prompt=$(cat <<EOF
You are Codex. Respond to Claude critically.
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
    if (( errors >= 3 )); then update_state "$round" aborted "$errors"; break; fi
    next_prompt="Codex failed to answer. Continue and provide concrete steps anyway."
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
echo "RUNLOG: $RUNLOG"
exit 0