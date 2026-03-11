#!/usr/bin/env bash
set -u
set -o pipefail

TOPIC="${1:?Topic is required}"
MAX_ROUNDS="${2:-5}"
OUTDIR="${3:-/home/dev/palomatika/duo-dialog-dual-$(date -u +%Y%m%dT%H%M%SZ)}"
mkdir -p "$OUTDIR"
LOG="$OUTDIR/shared_log.md"
REPORT="$OUTDIR/report.md"
STATE="$OUTDIR/state.json"
RUNLOG="$OUTDIR/run.log"
exec > >(tee -a "$RUNLOG") 2>&1

cat > "$LOG" <<EOF
# Dual Claude Dialog (Opus ↔ Sonnet)

Topic: $TOPIC
Started: $(date -u +"%Y-%m-%dT%H:%M:%SZ")
Rounds: $MAX_ROUNDS

---
EOF

update_state(){ cat > "$STATE" <<EOF
{"round":$1,"status":"$2","updated":"$(date -u +"%Y-%m-%dT%H:%M:%SZ")"}
EOF
}

call_model(){
  local model="$1" prompt="$2"
  timeout 140s bash -lc 'claude -p --model "$1" --permission-mode default "$2" 2>&1' _ "$model" "$prompt" | sed 's/\x1b\[[0-9;]*m//g' || true
}

log_turn(){
cat >> "$LOG" <<EOF

## Round $1 — $2

$3

---
EOF
}

update_state 0 running
promptA="You are Agent A (strict reviewer). Topic: $TOPIC. Give concrete technical analysis, risks, and minimal-change fixes."
for ((r=1;r<=MAX_ROUNDS;r++)); do
  update_state "$r" running
  a=$(call_model "opus" "$promptA")
  [[ -z "$(echo "$a" | tr -d '[:space:]')" ]] && a="(empty response)"
  log_turn "$r" "Claude Opus (Agent A)" "$a"

  promptB="You are Agent B (implementation lead). Respond to Agent A with concrete patch plan, rollback, and verification. Topic: $TOPIC\n\nAgent A said:\n$a"
  b=$(call_model "sonnet" "$promptB")
  [[ -z "$(echo "$b" | tr -d '[:space:]')" ]] && b="(empty response)"
  log_turn "$r" "Claude Sonnet (Agent B)" "$b"

  promptA="Continue from Agent B output. Focus on root cause certainty and production-safe rollout.\n\nAgent B said:\n$b"
done

summary=$(call_model "opus" "Summarize the dialog into: root cause, hotfix today, permanent architecture, migration plan, rollback checklist.\n\n$(cat "$LOG")")
cat > "$REPORT" <<EOF
# Dialog Summary

$summary
EOF
update_state "$MAX_ROUNDS" finished

echo "LOG: $LOG"
echo "REPORT: $REPORT"
echo "STATE: $STATE"
echo "RUNLOG: $RUNLOG"
