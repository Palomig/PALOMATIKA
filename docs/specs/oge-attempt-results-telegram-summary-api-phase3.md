# OGE Attempt Results Telegram Summary API (Phase 3)

Scope: Telegram/bot-friendly read API that adapts Phase 2 teacher result payloads into a compact summary plus canonical web links and Telegram Mini App links.

## Endpoint

### `GET /api/oge/attempts/{attempt}/telegram-summary`

- Auth: variant owner `teacher` or `admin`
- Purpose: stable bot-consumable summary contract for sending result notifications/messages
- Source of truth: internally builds from Phase 2 `buildAttemptResultPayload()` (web UI remains canonical renderer)

## Response shape

```json
{
  "success": true,
  "contract": {
    "name": "oge_attempt_telegram_result_summary",
    "version": 1
  },
  "attempt": {
    "id": 123,
    "status": "scored",
    "variant_id": 55,
    "variant_hash": "abcd1234",
    "is_custom": false,
    "student_id": 9,
    "student": { "id": 9, "name": "Student", "email": "student@example.com" }
  },
  "summary": {
    "tasks_total": 14,
    "answered_count": 14,
    "unanswered_count": 0,
    "correct_count": 10,
    "incorrect_count": 4,
    "unchecked_count": 0,
    "total_active_ms": 22000,
    "away_ms_total": 0,
    "duration_ms": 660000
  },
  "telegram": {
    "message_text": "OGE Results\n...",
    "buttons": {
      "variant_results": {
        "type": "web_app",
        "button": {
          "text": "Открыть в Telegram",
          "web_app": { "url": "https://mini-app.example/oge?startapp=oge_variant_55" }
        },
        "reply_markup": {
          "inline_keyboard": [[
            {
              "text": "Открыть в Telegram",
              "web_app": { "url": "https://mini-app.example/oge?startapp=oge_variant_55" }
            }
          ]]
        }
      },
      "attempt_results": {
        "type": "url",
        "button": {
          "text": "Открыть в Telegram",
          "url": "https://app/.../teacher/oge/variants/55/results?attempt=123#attempt-123"
        },
        "reply_markup": {
          "inline_keyboard": [[
            {
              "text": "Открыть в Telegram",
              "url": "https://app/.../teacher/oge/variants/55/results?attempt=123#attempt-123"
            }
          ]]
        }
      }
    },
    "task_statuses": [
      { "task_number": 6, "status": "correct", "code": "+" },
      { "task_number": 7, "status": "incorrect", "code": "-" }
    ],
    "links": {
      "variant_results_url": "https://app/.../teacher/oge/variants/55/results",
      "attempt_results_url": "https://app/.../teacher/oge/variants/55/results?attempt=123#attempt-123",
      "variant_results_mini_app_url": "https://t.me/<bot>?startapp=oge_variant_55",
      "attempt_results_mini_app_url": "https://t.me/<bot>?startapp=oge_attempt_123",
      "variant_results_button_url": "https://mini-app.example/oge?startapp=oge_variant_55",
      "attempt_results_button_url": "https://mini-app.example/oge?startapp=oge_attempt_123",
      "variant_results_preferred_url": "https://t.me/<bot>?startapp=oge_variant_55",
      "attempt_results_preferred_url": "https://t.me/<bot>?startapp=oge_attempt_123",
      "variant_results_startapp_payload": "oge_variant_55",
      "attempt_results_startapp_payload": "oge_attempt_123"
    },
    "config_validation": {
      "web_app_button_enabled": true,
      "is_valid": true,
      "bot_username_configured": true,
      "webapp_base_url": "https://mini-app.example/oge",
      "webapp_base_host": "mini-app.example",
      "webapp_domain": "mini-app.example",
      "issues": []
    }
  }
}
```

## Status code mapping (compact task list)

- `correct` -> `+`
- `incorrect` -> `-`
- `unchecked` -> `?`
- `unanswered` -> `.`

## Canonical rendering

- Telegram output must stay lightweight (summary + deep links)
- Full task table/details remain in `teacher.oge.results` web UI
- `attempt_results_url` uses row anchor `#attempt-{id}` for direct navigation
- `message_text` should use `*_preferred_url` (Mini App deep link when configured, otherwise web fallback)

## Telegram Mini App link configuration

- `TELEGRAM_BOT_USERNAME` (required for `*_mini_app_url` deep links and recommended for WebApp button flow)
- `TELEGRAM_WEBAPP_DOMAIN` (required for `web_app` buttons; must match BotFather `/setdomain` and the host of `TELEGRAM_WEBAPP_BASE_URL`)
- `TELEGRAM_WEBAPP_BASE_URL` (required for `web_app` button payload generation; falls back to plain web URL button if missing)
- `TELEGRAM_MINI_APP_LINK_SCHEME` (optional: `https` default for `https://t.me/...`, `tg` for `tg://resolve?...`)

### BotFather / WebApp checklist (hotfix 3.1)

- In BotFather, configure the Mini App domain via `/setdomain` for the same host as `TELEGRAM_WEBAPP_BASE_URL`
- Set `TELEGRAM_BOT_USERNAME` to the bot handle without `@`
- Set `TELEGRAM_WEBAPP_DOMAIN` to the BotFather domain host (for runtime validation/debugging)
- Set `TELEGRAM_WEBAPP_BASE_URL` to the absolute Mini App page URL used in `web_app.url`
- If WebApp config is incomplete, consumers should use `telegram.buttons.*.reply_markup` (URL mode) or plain `telegram.links.*_url` fallback

## StartApp payloads

- Variant results payload: `oge_variant_{variantId}`
- Attempt results payload: `oge_attempt_{attemptId}`
- Mini App client should route these payloads to the OGE results view
