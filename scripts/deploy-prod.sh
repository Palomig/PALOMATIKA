#!/usr/bin/env bash
#
# Прямая доставка PALOMATIKA на прод (Timeweb) с dev-VPS.
#
# Почему не GitHub Actions: FTP Timeweb из Actions регулярно отваливался по
# таймауту, причём первый шаг мог помечаться зелёным без реального трансфера —
# main уезжал вперёд, а прод молча оставался на старом коде. Здесь каждый файл
# после заливки сверяется по MD5, и «задеплоено» означает «проверено».
#
# Что делает:
#   1. Берёт список изменённых файлов от последнего задеплоенного коммита
#      (маркер лежит на проде) до указанной ревизии.
#   2. Заливает по FTP только те, чей хеш на проде не совпал (с ретраями).
#   3. Сверяет MD5 каждого залитого файла.
#   4. Обновляет маркер и дёргает deploy:refresh (кэши + миграции).
#
# Использование:
#   scripts/deploy-prod.sh                    # origin/main от маркера на проде
#   scripts/deploy-prod.sh --rev HEAD         # другая ревизия
#   scripts/deploy-prod.sh --base <ref>       # первый запуск / маркер не в счёт
#   scripts/deploy-prod.sh --dry-run          # только показать план
#   scripts/deploy-prod.sh --no-refresh       # без deploy:refresh
#
# Секреты: TMW_PSW в /home/dev/.agent-secrets/timeweb.env,
#          DEPLOY_WEBHOOK_SECRET в .mcp.json репозитория.

set -uo pipefail

FTP_HOST="cw95865.tmweb.ru"
FTP_USER="cw95865"
FTP_ROOT="/OGE"
PROD_URL="https://cw95865.tmweb.ru"
MARKER_PATH="storage/app/deployed-commit.txt"
SECRETS_FILE="/home/dev/.agent-secrets/timeweb.env"
UPLOAD_RETRIES=3

REV="origin/main"
BASE=""
DRY_RUN=0
DO_REFRESH=1

while [ $# -gt 0 ]; do
  case "$1" in
    --rev) REV="$2"; shift 2 ;;
    --base) BASE="$2"; shift 2 ;;
    --dry-run) DRY_RUN=1; shift ;;
    --no-refresh) DO_REFRESH=0; shift ;;
    -h|--help) sed -n '2,30p' "$0"; exit 0 ;;
    *) echo "Неизвестный аргумент: $1" >&2; exit 2 ;;
  esac
done

cd "$(git rev-parse --show-toplevel)" || exit 1

# ── Секреты ──────────────────────────────────────────────────────────────────
if [ ! -f "$SECRETS_FILE" ]; then
  echo "Нет файла с доступами: $SECRETS_FILE" >&2
  exit 1
fi
# shellcheck disable=SC1090
set -a; . "$SECRETS_FILE"; set +a

if [ -z "${TMW_PSW:-}" ]; then
  echo "В $SECRETS_FILE нет TMW_PSW" >&2
  exit 1
fi

DEPLOY_SECRET="${DEPLOY_WEBHOOK_SECRET:-}"
if [ -z "$DEPLOY_SECRET" ] && [ -f .mcp.json ]; then
  DEPLOY_SECRET=$(python3 -c "
import json, sys
try:
    conf = json.load(open('.mcp.json'))
except Exception:
    sys.exit()
def walk(node):
    if isinstance(node, dict):
        for key, value in node.items():
            if key == 'DEPLOY_WEBHOOK_SECRET' and isinstance(value, str):
                print(value); return True
            if walk(value): return True
    elif isinstance(node, list):
        for item in node:
            if walk(item): return True
    return False
walk(conf)
" 2>/dev/null)
fi

ftp_url() { printf 'ftp://%s%s/%s' "$FTP_HOST" "$FTP_ROOT" "$1"; }

# MD5 файла на проде; пустая строка, если файла нет.
remote_md5() {
  curl -s --max-time 60 --ftp-pasv --user "$FTP_USER:$TMW_PSW" "$(ftp_url "$1")" 2>/dev/null \
    | md5sum 2>/dev/null | awk '{print $1}'
}

EMPTY_MD5=$(printf '' | md5sum | awk '{print $1}')

upload() {
  curl -s --show-error --max-time 180 --ftp-pasv --ftp-create-dirs \
    --user "$FTP_USER:$TMW_PSW" -T "$1" "$(ftp_url "$1")" 2>&1
}

# ── База сравнения ───────────────────────────────────────────────────────────
if [ -z "$BASE" ]; then
  BASE=$(curl -s --max-time 45 --ftp-pasv --user "$FTP_USER:$TMW_PSW" \
    "$(ftp_url "$MARKER_PATH")" 2>/dev/null | tr -d '[:space:]')
fi

if [ -z "$BASE" ]; then
  cat >&2 <<'MSG'
На проде нет маркера последнего деплоя, и --base не задан.
Не с чем сравнивать: укажи явно, от какого коммита считать изменения, например
  scripts/deploy-prod.sh --base <sha последнего реально задеплоенного мёржа>
MSG
  exit 1
fi

if ! git cat-file -e "${BASE}^{commit}" 2>/dev/null; then
  echo "Базовый коммит $BASE не найден локально — сделай git fetch origin" >&2
  exit 1
fi

REV_SHA=$(git rev-parse "$REV") || exit 1
BASE_SHA=$(git rev-parse "$BASE")

echo "База:     $BASE_SHA ($(git log -1 --format=%s "$BASE_SHA" | cut -c1-60))"
echo "Ревизия:  $REV_SHA ($(git log -1 --format=%s "$REV_SHA" | cut -c1-60))"

if [ "$BASE_SHA" = "$REV_SHA" ]; then
  echo "Прод уже на этой ревизии — заливать нечего."
  exit 0
fi

# ── Что заливаем ─────────────────────────────────────────────────────────────
# Исключения повторяют старый workflow: на проде не нужны ни тесты, ни доки,
# ни vendor (он там свой), ни служебные каталоги агентов.
is_runtime_file() {
  case "$1" in
    .github/*|.claude/*|.vscode/*|.idea/*|agent-board/*) return 1 ;;
    tests/*|phpunit.xml|node_modules/*|vendor/*) return 1 ;;
    docs/*|*.md|.gitignore|.gitattributes|.editorconfig) return 1 ;;
    scripts/deploy-prod.sh|scripts/blade-lint.php) return 1 ;;
    *) return 0 ;;
  esac
}

mapfile -t CHANGED < <(git diff --name-only --diff-filter=ACMR "$BASE_SHA" "$REV_SHA")
mapfile -t DELETED < <(git diff --name-only --diff-filter=D "$BASE_SHA" "$REV_SHA")

FILES=()
for path in "${CHANGED[@]}"; do
  is_runtime_file "$path" && FILES+=("$path")
done

GONE=()
for path in "${DELETED[@]}"; do
  is_runtime_file "$path" && GONE+=("$path")
done

echo "Изменено файлов в ревизии: ${#CHANGED[@]}, из них рантайм-файлов: ${#FILES[@]}"
[ ${#GONE[@]} -gt 0 ] && printf 'Удалены в репозитории (на проде НЕ удаляются автоматически):\n%s\n' "$(printf '  %s\n' "${GONE[@]}")"

if [ ${#FILES[@]} -eq 0 ]; then
  echo "Рантайм-файлов не изменилось."
else
  printf '  %s\n' "${FILES[@]}"
fi

# ── Собираются ли шаблоны ────────────────────────────────────────────────────
# Blade-ловушка: HTML-атрибут может совпасть с директивой (@error на <img> уже
# уронил прод в 500). Проверяем только те шаблоны, что едут этим деплоем: чужие
# давние поломки не наше дело и блокировать доставку не должны.
BLADE=()
for path in "${FILES[@]}"; do
  case "$path" in *.blade.php) [ -f "$path" ] && BLADE+=("$path") ;; esac
done

if [ ${#BLADE[@]} -gt 0 ] && [ -f scripts/blade-lint.php ]; then
  if ! php scripts/blade-lint.php "${BLADE[@]}"; then
    echo "Деплой остановлен: шаблон не собирается в валидный PHP — на проде это 500." >&2
    exit 1
  fi
  echo "Шаблоны собираются: ${#BLADE[@]}"
fi

if [ "$DRY_RUN" = "1" ]; then
  echo "(--dry-run: ничего не залито)"
  exit 0
fi

# ── Заливка со сверкой ───────────────────────────────────────────────────────
uploaded=0; skipped=0; failed=0
FAILED_FILES=()

for path in "${FILES[@]}"; do
  [ -f "$path" ] || { echo "  ПРОПУСК (нет локально): $path"; continue; }

  local_md5=$(md5sum "$path" | awk '{print $1}')
  before=$(remote_md5 "$path")

  if [ "$before" = "$local_md5" ]; then
    echo "  = $path (уже совпадает)"
    skipped=$((skipped + 1))
    continue
  fi

  ok=0
  for attempt in $(seq 1 $UPLOAD_RETRIES); do
    err=$(upload "$path")
    after=$(remote_md5 "$path")

    if [ "$after" = "$local_md5" ]; then
      echo "  ✓ $path"
      ok=1
      break
    fi

    echo "  … попытка $attempt не сошлась по хешу для $path ${err:+($err)}"
    sleep 3
  done

  if [ "$ok" = "1" ]; then
    uploaded=$((uploaded + 1))
  else
    echo "  ✗ НЕ ЗАЛИТО: $path"
    failed=$((failed + 1))
    FAILED_FILES+=("$path")
  fi
done

echo
echo "Залито: $uploaded, совпадало: $skipped, не удалось: $failed"

if [ "$failed" -gt 0 ]; then
  echo "Деплой НЕ завершён — маркер не обновляю, deploy:refresh не зову." >&2
  printf '  %s\n' "${FAILED_FILES[@]}" >&2
  exit 1
fi

# ── Маркер ───────────────────────────────────────────────────────────────────
marker_tmp=$(mktemp)
printf '%s\n' "$REV_SHA" > "$marker_tmp"
curl -s --show-error --max-time 60 --ftp-pasv --ftp-create-dirs \
  --user "$FTP_USER:$TMW_PSW" -T "$marker_tmp" "$(ftp_url "$MARKER_PATH")" >/dev/null
marker_now=$(curl -s --max-time 45 --ftp-pasv --user "$FTP_USER:$TMW_PSW" "$(ftp_url "$MARKER_PATH")" | tr -d '[:space:]')
rm -f "$marker_tmp"

if [ "$marker_now" = "$REV_SHA" ]; then
  echo "Маркер на проде обновлён: $REV_SHA"
else
  echo "ВНИМАНИЕ: маркер не обновился (на проде «$marker_now»). Следующий деплой посчитает изменения от старой базы." >&2
fi

# ── Кэши и миграции ──────────────────────────────────────────────────────────
if [ "$DO_REFRESH" = "1" ]; then
  if [ -z "$DEPLOY_SECRET" ]; then
    echo "DEPLOY_WEBHOOK_SECRET не найден — deploy:refresh пропущен, вызови вручную." >&2
  else
    echo -n "deploy:refresh… "
    refresh=$(curl -s --max-time 180 -X POST "$PROD_URL/api/deploy/refresh" \
      -H "X-Deploy-Secret: $DEPLOY_SECRET" -H "Content-Type: application/json")
    echo "${refresh:-(пустой ответ)}"
  fi
fi

echo "Готово."
