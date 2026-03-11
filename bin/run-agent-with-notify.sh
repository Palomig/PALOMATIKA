#!/usr/bin/env bash
set -euo pipefail

# Usage:
# run-agent-with-notify.sh codex "<prompt>"
# run-agent-with-notify.sh claude "<prompt>"

AGENT="${1:-}"
PROMPT="${2:-}"

if [[ -z "$AGENT" || -z "$PROMPT" ]]; then
  echo "Usage: $0 <codex|claude> <prompt>" >&2
  exit 2
fi

START_TS="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
STATUS="done"
EXIT_CODE=0

run_cmd() {
  case "$AGENT" in
    codex)
      codex exec --full-auto "$PROMPT"
      ;;
    claude)
      claude -p "$PROMPT"
      ;;
    *)
      echo "Unsupported agent: $AGENT" >&2
      return 2
      ;;
  esac
}

if ! OUTPUT="$(run_cmd 2>&1)"; then
  EXIT_CODE=$?
  STATUS="failed"
else
  OUTPUT="${OUTPUT}"
fi

# Codex often exits 0 even when an internal executed command failed.
# Promote status to failed when output clearly contains command failure markers.
if [[ "$STATUS" == "done" ]]; then
  if grep -Eiq "(usage_limit_reached|You've hit your limit| exited [1-9][0-9]*|error:|Permission denied|Command executed: .*Result: process exited with code [1-9])" <<<"$OUTPUT"; then
    STATUS="failed"
  fi
fi

# Try to detect latest commit hash and auto-push any new commits (best effort)
COMMIT="n/a"
PUSH_STATUS="skip"
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  COMMIT="$(git log --oneline -n 1 2>/dev/null | awk '{print $1}')"
  [[ -z "$COMMIT" ]] && COMMIT="n/a"

  # If branch is ahead of upstream, push immediately.
  # IMPORTANT: only auto-push from primary operator account to avoid
  # credential prompts/failures under secondary linux profiles (e.g. codex2).
  CURRENT_USER="$(id -un 2>/dev/null || echo unknown)"
  if git rev-parse --abbrev-ref --symbolic-full-name @{u} >/dev/null 2>&1; then
    AHEAD="$(git rev-list --left-right --count @{u}...HEAD 2>/dev/null | awk '{print $2}')"
    AHEAD="${AHEAD:-0}"
    if [[ "$AHEAD" =~ ^[0-9]+$ ]] && (( AHEAD > 0 )); then
      if [[ "$CURRENT_USER" != "dev" ]]; then
        PUSH_STATUS="handoff-required"
      elif git push origin HEAD >/dev/null 2>&1; then
        PUSH_STATUS="ok"
      else
        # One retry after rebase/pull attempt
        git pull --rebase --autostash >/dev/null 2>&1 || true
        if git push origin HEAD >/dev/null 2>&1; then
          PUSH_STATUS="ok-after-retry"
        else
          PUSH_STATUS="failed"
          STATUS="failed"
        fi
      fi
    else
      PUSH_STATUS="up-to-date"
    fi
  else
    PUSH_STATUS="no-upstream"
  fi
fi

# Keep summary compact and safe for shell/CLI
SUMMARY_RAW="agent=${AGENT}; status=${STATUS}; exit=${EXIT_CODE}; started=${START_TS}; commit=${COMMIT}; push=${PUSH_STATUS}"
SUMMARY="${SUMMARY_RAW//$'\n'/ }"

# Fire wake event (best effort, never fail hard)
openclaw system event --text "$SUMMARY" --mode now >/dev/null 2>&1 || true

# Print agent output for normal log consumption
printf "%s\n" "$OUTPUT"
exit $EXIT_CODE
