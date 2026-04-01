# Palomatika PWA Migration Design

**Date:** 2026-04-01  
**Status:** Approved  
**Branch:** claude/telegram-stars-premium → new branch per feature

---

## Context

Palomatika is migrating fully away from Telegram Mini App to a standalone PWA. The Telegram mini app (`/tg/*`) stays active during migration, then gets removed once users have transitioned. The task database (`/topics/*`, `storage/app/tasks/`, all task services) is never touched.

---

## 1. Domain Structure

```
palomatika.ru              → landing page ("Я ученик" / "Я репетитор") + /topics/*
student.palomatika.ru      → PWA for students
teacher.palomatika.ru      → PWA for teachers/tutors
parent.palomatika.ru       → (future)
```

Each subdomain has its own `manifest.json`, service worker, and app icon — separate install experience like Uber / Uber Driver.

Laravel serves all subdomains from one codebase via subdomain route groups.

---

## 2. Authentication

Replace Telegram `initData` HMAC auth with **Laravel Socialite OAuth**:

- **VK** (`/auth/vk`)
- **Yandex** (`/auth/yandex`)
- **Google** (`/auth/google`)
- Callback: `/auth/callback/{provider}`

The `users` table already has `oauth_provider` / `oauth_id` columns — values change from `telegram` to `vk`/`yandex`/`google`.

No email/password auth. No Telegram auth (except legacy `/tg/*` during migration).

### Telegram user migration flow

1. Banner appears in `/tg/*`: *"Palomatika переезжает — установите новое приложение"*
2. "Перейти" button generates a one-time token, opens `student.palomatika.ru/migrate?token=XXX`
3. User links VK / Yandex / Google → history, profile, attempts are preserved
4. Users who don't migrate start fresh with a new account

---

## 3. PWA Technical Requirements

For each subdomain (`student.*`, `teacher.*`):

| Requirement | Detail |
|---|---|
| `manifest.json` | name, icons, `display: standalone`, `start_url`, theme color |
| Service Worker | cache app shell (HTML/CSS/JS), offline fallback page |
| Icons | 192×192 and 512×512 PNG, separate per subdomain |
| HTTPS | already on palomatika.ru ✓ |

### Install prompts

- **Android**: native `beforeinstallprompt` event → shown as install button in app UI
- **iOS**: custom in-app overlay with visual guide: *"Share → Add to Home Screen"* (triggered on first visit, dismissable)

App Store / Google Play publishing deferred — possible later via Capacitor wrapper.

---

## 4. Route Structure

### `student.palomatika.ru`
```
/                → dashboard (or onboarding for new users)
/onboarding      → collect grade, city
/mini            → mini-OGE (~10 min)
/test/full       → full OGE variant
/test/{id}       → active test session
/results/{id}    → test results
/history         → attempt history
/profile         → user profile
/migrate         → Telegram account migration
/auth/{provider} → OAuth redirect
/auth/callback/{provider} → OAuth callback
/manifest.json   → PWA manifest
/sw.js           → Service Worker
```

### `teacher.palomatika.ru`
```
/                → teacher dashboard
/students        → student list
/lessons         → lessons and homework
/auth/{provider} → OAuth redirect
/auth/callback/{provider} → OAuth callback
/manifest.json   → PWA manifest
/sw.js           → Service Worker
```

### `palomatika.ru` (unchanged core)
```
/topics/*        → task database pages (NEVER TOUCH)
/tg/*            → Telegram mini app (remove after migration)
/                → new landing page
```

---

## 5. Premium & Payments

**Removed for now.** No premium features, no Telegram Stars, no payment integration. All users get full access. Payments will be added in a separate phase (likely ЮKassa).

---

## 6. What Gets Removed

Only old website pages/controllers unrelated to task database or mini app:

- Old marketing/landing views and controllers
- Email/password registration and login
- Old routes not in `/tg/*` or `/topics/*`
- `TestPdfController` — replaced by new PWA controllers

**Sacred — never delete:**
- `storage/app/tasks/` (all JSON, including geometry)
- SVG data baked into DB
- `TaskDataService`, `GeometrySvgRenderer`, `OgeVariantBuilderService`, `OgeAttemptService`, `TaskAnswerResolver`
- `/topics/*` controller and views
- `/materials/*` controller and views (`JarvisMaterialPageController`)
- All database migrations
- `database/migrations/`

---

## 7. Implementation Phases

### Phase 1: Infrastructure
- Configure subdomains in Nginx + SSL (Let's Encrypt)
- Add subdomain route groups in Laravel
- Install Laravel Socialite, configure VK/Yandex/Google providers

### Phase 2: Auth
- OAuth login/register flow for student and teacher subdomains
- Session management per subdomain
- Migration page with one-time token flow

### Phase 3: Student PWA
- Port `/tg/*` student views to `student.palomatika.ru`
- Remove Telegram SDK dependencies from views
- Add `manifest.json` + service worker
- Add install prompt (Android + iOS guide)

### Phase 4: Teacher PWA
- Port `/tg/teacher/*` views to `teacher.palomatika.ru`
- Add `manifest.json` + service worker

### Phase 5: Migration & Cleanup
- Add migration banner to `/tg/*`
- Update `palomatika.ru` landing page
- Remove old website pages/controllers
- After user migration complete: remove `/tg/*`

---

## 8. Bug Fixes (Separate Track)

Known bugs in OGE variant generation (wrong images, wrong answer scoring, task 19 generating 3 correct answers instead of 2) will be addressed in a **separate branch** after PWA migration is complete. These are backend logic bugs unrelated to the Telegram→PWA migration.
