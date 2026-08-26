#!/usr/bin/env bash
#
# Выпуск готовой фичи на прод: ветка → main → доставка.
#
# Схема веток (с 2026-08-26):
#   claude/**  → авто-мёрж в develop   — песочница, на прод не едет
#   promote.sh → мёрж в main           — main это ровно то, что на проде
#
# `develop` в `main` не мержится никогда. Ветку фичи ответвляй от `main`:
#   git fetch origin && git worktree add ../palomatika-<фича> -b claude/<фича> origin/main
#
# Использование:
#   scripts/promote.sh claude/print-variants
#   scripts/promote.sh claude/print-variants --dry-run   # показать, что поедет
#   scripts/promote.sh claude/print-variants --no-deploy # влить в main, но не доставлять
#
set -uo pipefail

BRANCH=""
DRY_RUN=0
DO_DEPLOY=1

while [ $# -gt 0 ]; do
  case "$1" in
    --dry-run) DRY_RUN=1; shift ;;
    --no-deploy) DO_DEPLOY=0; shift ;;
    -h|--help) sed -n '2,20p' "$0"; exit 0 ;;
    -*) echo "Неизвестный аргумент: $1" >&2; exit 2 ;;
    *) BRANCH="$1"; shift ;;
  esac
done

if [ -z "$BRANCH" ]; then
  echo "Укажи ветку: scripts/promote.sh claude/<фича>" >&2
  exit 2
fi

ROOT=$(git rev-parse --show-toplevel) || exit 1
cd "$ROOT" || exit 1

git fetch origin --prune || exit 1

BRANCH="${BRANCH#origin/}"
if [ "$BRANCH" = "develop" ] || [ "$BRANCH" = "main" ]; then
  cat >&2 <<'MSG'
develop и main через promote.sh не выпускаются.
develop — интеграционная песочница: влить её в main значит утащить на прод
всю незрелую работу разом. Выпускай ветку конкретной фичи.
MSG
  exit 1
fi

if ! git rev-parse --verify "origin/$BRANCH" >/dev/null 2>&1; then
  echo "На origin нет ветки $BRANCH" >&2
  exit 1
fi

echo "=== Что поедет на прод: origin/$BRANCH → main ==="
git log --oneline "origin/main..origin/$BRANCH" | sed 's/^/  /'
echo
echo "=== Файлы ==="
git diff --name-status "origin/main...origin/$BRANCH" | sed 's/^/  /'
echo
echo "=== Миграции ==="
MIGR=$(git diff --name-only --diff-filter=A "origin/main...origin/$BRANCH" -- database/migrations/)
if [ -n "$MIGR" ]; then
  echo "$MIGR" | sed 's/^/  /'
  echo "  ↑ прогонятся на проде через deploy:refresh"
else
  echo "  нет"
fi

if [ "$DRY_RUN" = "1" ]; then
  echo
  echo "(--dry-run: ничего не влито и не залито)"
  exit 0
fi

# Мёрж делаем в отдельном worktree — рабочее дерево вызывающего не трогаем.
TMP="$(mktemp -d /tmp/palomatika-promote-XXXX)/tree"
cleanup() {
  git -C "$ROOT" worktree remove "$TMP" --force >/dev/null 2>&1
  git -C "$ROOT" branch -D promote/main >/dev/null 2>&1
  rm -rf "$(dirname "$TMP")"
}
trap cleanup EXIT

git worktree add "$TMP" -B promote/main origin/main >/dev/null 2>&1 || {
  echo "Не удалось создать worktree для мёржа" >&2; exit 1; }

cd "$TMP" || exit 1

if ! git merge --no-ff -m "Promote: $BRANCH -> main" "origin/$BRANCH"; then
  git merge --abort || true
  echo "Конфликт при мёрже $BRANCH в main — разруливай вручную." >&2
  exit 1
fi

if ! git push origin HEAD:main; then
  echo "Не удалось запушить main (кто-то обогнал?) — сделай git fetch и повтори." >&2
  exit 1
fi
echo "main обновлён: $(git rev-parse --short HEAD)"

if [ "$DO_DEPLOY" != "1" ]; then
  echo "(--no-deploy: на прод не доставляли; штатно — scripts/deploy-prod.sh)"
  exit 0
fi

# Деплой из этого же дерева: оно ровно на выпускаемой ревизии, чего и требует
# стоп-кран в deploy-prod.sh. Секрет вебхука берётся из .mcp.json (он в репо).
git fetch origin main >/dev/null 2>&1
# Не exec: иначе trap не отработает и временный worktree останется висеть.
./scripts/deploy-prod.sh --rev HEAD
exit $?
