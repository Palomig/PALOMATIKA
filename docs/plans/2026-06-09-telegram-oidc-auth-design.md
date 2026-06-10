# Telegram OIDC Auth — переход на новую авторизацию (Часть A)

**Дата:** 2026-06-09
**Статус:** дизайн одобрен, готов к плану реализации
**Эпик:** замена старой Telegram-авторизации на официальный Telegram OAuth 2.0 / OpenID Connect

---

## 1. Проблема и цель

**Проблема:** один ученик получает разные аккаунты, заходя из Telegram Mini App и из браузера.
Корень — в коде: веб-логин ученика (`Pwa/AuthController`) предлагает провайдеров `vkontakte, google, yandex`, а **Telegram среди них нет**. В Mini App ученик опознаётся по `initData` (`oauth_provider='telegram'` + `oauth_id`=tg id), а на вебе жмёт Google/Yandex/VK → отдельная учётка с другим провайдером.

**Цель (этот эпик, Часть A):** дать на вебе **Telegram-вход** через новый OIDC, ключующий по тому же `oauth_id`, что и Mini App, → веб и Mini App ведут в **один** аккаунт. Заменить старый Login Widget и бот-диплинк логин.

**Вне scope (Часть B, отдельный эпик):** детект дублей по совпадению `name`+`last_name`+`grade_num`+`school_number` и ручное слияние учётки учителем/админом. Google/Yandex/VK остаются как есть (свои отдельные аккаунты — принято).

---

## 2. Решение об идентичности (без новой таблицы)

Сохраняем текущую схему `users.oauth_provider` + `users.oauth_id` (UNIQUE). Никакой мультилинковки/новых таблиц.

Сейчас существуют **три** разных резолвера telegram-юзера (widget в `SocialAuthController::findOrCreateTelegramUser`, Mini App в `TelegramMiniAppAuthService`, бот в `TelegramBotAuthController`). Консолидируем в **один** `resolveTelegramUser(int $tgId, ?string $username, ?string $name, ?string $photo): User`, который используют OIDC и initData (и бот, если останется). Логика: найти по `('telegram', $tgId)` → обновить аватар/username → иначе создать. Это гарантирует, что Telegram-идентичность ключуется одинаково во всех путях и не «дрейфует».

`sub` из OIDC = числовой Telegram user id = тот же `oauth_id`, что кладёт Mini App → вернувшийся юзер матчится без дублей.

---

## 3. Новый OIDC-флоу (Authorization Code + PKCE)

Официальные эндпоинты Telegram (discovery `https://oauth.telegram.org/.well-known/openid-configuration`):
- **authorize:** `https://oauth.telegram.org/auth`
- **token:** `https://oauth.telegram.org/token` (Basic `base64(client_id:client_secret)`)
- **jwks:** `https://oauth.telegram.org/.well-known/jwks.json`
- **userinfo:** отсутствует — все claims в `id_token` (`sub`, `name`, `preferred_username`, `picture`, `phone_number`).

**Реализация:** тонкий сервис `app/Services/TelegramOidcService.php` (без Socialite-абстракции — у Telegram нет userinfo, id_token достаточно). Переиспользуется и в `SocialAuthController` (главный домен), и в `Pwa/AuthController` (поддомены).

**Шаги:**
1. `GET /auth/telegram/redirect` — генерим `state` (CSRF), `nonce`, `code_verifier`/`code_challenge` (S256), сохраняем + `origin` (исходный поддомен) в сессию; редирект на authorize с
   `client_id, redirect_uri, response_type=code, scope="openid profile", state, nonce, code_challenge, code_challenge_method=S256`.
2. `GET /auth/telegram/callback` (один зарегистрированный redirect URI на `palomatika.ru`):
   - сверяем `state` с сессией; при ошибке/`error` — редирект на login с сообщением;
   - обмен `code` → `id_token` на `/token` (Basic auth, `code_verifier`);
   - **верификация JWT** через `firebase/php-jwt` (`JWK::parseKeySet` по JWKS, RS256), кэш ключей (по `kid`, TTL ~1ч);
   - валидируем `iss=https://oauth.telegram.org`, `aud=<client_id/bot id>`, `exp`, `nonce`;
   - claims → `resolveTelegramUser()` → `Auth::login()`, `session()->regenerate()`;
   - редирект на `origin`-поверхность по роли (как делает существующий dashboard-роутинг).

**Мульти-домен:** `SESSION_DOMAIN=.palomatika.ru` → cookie сессии общий для всех поддоменов. Достаточно **одного** callback на главном домене; после логина сессия валидна на student/teacher/parent. Кнопка «Войти через Telegram» на поддомене инициирует флоу с `redirect_uri` = главный callback и `origin`=свой поддомен в `state`.

---

## 4. Конфигурация

`config/services.php`:
```php
'telegram' => [
    // ...существующее (bot_username, bot_token, webhook_secret)...
    'oidc' => [
        'client_id'     => env('TELEGRAM_OIDC_CLIENT_ID', '8047450650'),
        'client_secret' => env('TELEGRAM_OIDC_CLIENT_SECRET'),
        'redirect'      => env('TELEGRAM_OIDC_REDIRECT', 'https://palomatika.ru/auth/telegram/callback'),
    ],
],
```
Новые env: `TELEGRAM_OIDC_CLIENT_ID`, `TELEGRAM_OIDC_CLIENT_SECRET`, `TELEGRAM_OIDC_REDIRECT`.
**В BotFather** (Bot Settings → Web Login): зарегистрировать Allowed URL / Redirect URI `https://palomatika.ru/auth/telegram/callback`.

---

## 5. Что удаляем / что оставляем

**Удаляем (старая Telegram-авторизация):**
- Legacy Login Widget: `SocialAuthController::telegramCallback`, `verifyTelegramAuth`, старый `findOrCreateTelegramUser`; route `GET /auth/telegram/callback` (старый); виджет-скрипт `data-telegram-login` в login-вьюхах.
- Бот-диплинк веб-логин: `TelegramBotAuthController::generateToken`, `checkToken`, `login(token)`; роуты `/api/telegram/generate-token`, `/api/telegram/check-token/{token}`, `/auth/telegram/login/{token}`.

**Оставляем без изменений:**
- `TelegramBotAuthController::webAppLogin` (Mini App initData) и `webhook` (сообщения, OGE-итоги, premium, онбординг), `sessionCheck`, `diag`.
- Mini App initData флоу (`MiniAppAuthController`, `TelegramMiniAppAuthService`) — переключаем его резолвер юзера на общий `resolveTelegramUser()`.
- Google / Yandex / VK (Socialite) — без изменений.

**Риск:** бот-диплинк логин мог использоваться в онбординге. Перед удалением — проверить отсутствие активных ссылок/кнопок; роуты заменить на 404. Webhook/мессенджинг не трогаем.

---

## 6. Безопасность

- PKCE (S256), `code_verifier` только в сессии.
- `state` — CSRF-защита, одноразовый, сверяется и удаляется на callback.
- `nonce` в id_token сверяется с сессией.
- Обязательная верификация подписи id_token по JWKS; проверка `iss`, `aud` (= наш client_id/bot id), `exp`, `iat`.
- `client_secret` только в env, не в репозитории.
- Аудит-события `telegram_oidc_login_success` / `telegram_oidc_login_failed` через существующий `AuditLogger` (как у текущих флоу).

---

## 7. Тестирование (Feature)

- Успешный callback с валидным id_token → юзер создан/найден, сессия установлена.
- Вернувшийся юзер: запись от Mini App (`oauth_provider='telegram'`,`oauth_id`) + веб OIDC с тем же `sub` → один и тот же `users.id` (ключевой тест на дубли).
- `state` mismatch → ошибка, не логинит.
- Невалидная/чужая подпись id_token → 403, не логинит.
- `aud`/`iss`/`exp` mismatch → отказ.
- Удалённые роуты (`/auth/telegram/login/{token}`, `/api/telegram/generate-token`) → 404.
- JWKS-верификация мокается (фиктивный key set) для детерминизма.

---

## 8. Открытые вопросы на этап реализации

- Точный формат `aud` от Telegram (bot id как число/строка) — свериться на первом реальном обмене.
- Нужен ли отдельный per-subdomain redirect URI вместо одного центрального (если кросс-доменный hop окажется нежелателен по UX) — по умолчанию один центральный.
- Где именно в login-вьюхах поддоменов разместить кнопку «Войти через Telegram» (основная, заметная).

---

## Источники
- https://core.telegram.org/widgets/login
- https://oauth.telegram.org/.well-known/openid-configuration
- https://kulikovd.medium.com/how-to-add-telegram-login-to-the-website-with-new-oidc-flow-4a1bb8ad03c4
