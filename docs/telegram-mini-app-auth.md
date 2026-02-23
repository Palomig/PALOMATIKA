# Telegram Mini App Instant Auth (WebApp `initData`)

Palomatika supports direct login inside Telegram Mini App using `Telegram.WebApp.initData`.

## Flow

- User opens the site inside Telegram Mini App.
- On login page, tapping `Войти через Telegram` first tries `POST /api/auth/telegram/webapp-login` with:
  - `initData` (raw signed query string from `Telegram.WebApp.initData`)
  - `initDataUnsafe` (optional fallback payload from `Telegram.WebApp.initDataUnsafe`)
- Backend verifies Telegram signature using `TELEGRAM_BOT_TOKEN`.
- Backend finds/creates user by:
  - `oauth_provider = telegram`
  - `oauth_id = <telegram user id>`
- Backend creates Laravel web session and returns JSON with `redirect_to`.
- If `Telegram.WebApp` is detected, the client uses only `POST /api/auth/telegram/webapp-login` (strict Mini App auth mode).
- If Mini App `initData` is missing/invalid, the page shows an inline retryable error and does not open the bot or call `/api/telegram/generate-token`.
- The existing bot `/start` token fallback (`/api/telegram/generate-token` + polling) is used only in external/non-Mini App browser context.

## Required config

- `TELEGRAM_BOT_TOKEN` (required for WebApp `initData` signature verification and bot fallback flow)
- `TELEGRAM_BOT_USERNAME` (required for bot fallback deep link generation)
- `TELEGRAM_WEBAPP_BASE_URL` (recommended for Telegram `web_app` buttons / Mini App links)
- `TELEGRAM_WEBAPP_DOMAIN` (recommended for BotFather `/setdomain` validation and runtime checks)

## Notes

- The WebApp login endpoint is session-backed and intentionally registered on `web` middleware (`/api/auth/telegram/webapp-login`) so Laravel session auth works immediately.
- External browsers and non-Mini App usage keep the existing bot-based fallback behavior.
