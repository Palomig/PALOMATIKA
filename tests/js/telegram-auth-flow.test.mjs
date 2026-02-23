import test from 'node:test';
import assert from 'node:assert/strict';

import { runTelegramAuthStart, TELEGRAM_AUTH_ERRORS } from '../../public/js/telegram-auth.mjs';

test('Mini App context does not invoke bot fallback generate-token flow', async () => {
  let miniAppCalls = 0;
  let botFallbackCalls = 0;

  const result = await runTelegramAuthStart({
    telegramGlobal: { WebApp: { initData: 'auth_date=1&hash=abc' } },
    tryMiniAppLogin: async () => {
      miniAppCalls += 1;
      return { success: true, redirectTo: '/dashboard' };
    },
    startBotFallback: async () => {
      botFallbackCalls += 1;
      return { mode: 'bot_fallback_started' };
    },
  });

  assert.equal(miniAppCalls, 1);
  assert.equal(botFallbackCalls, 0);
  assert.equal(result.mode, 'miniapp_success');
});

test('Mini App invalid initData returns inline error path and never falls back to bot flow', async () => {
  let botFallbackCalls = 0;

  const result = await runTelegramAuthStart({
    telegramGlobal: { WebApp: { initData: 'invalid' } },
    tryMiniAppLogin: async () => ({ success: false, error: 'Неверные данные Telegram Mini App. Попробуйте снова.' }),
    startBotFallback: async () => {
      botFallbackCalls += 1;
      return { mode: 'bot_fallback_started' };
    },
  });

  assert.equal(botFallbackCalls, 0);
  assert.equal(result.mode, 'miniapp_error');
  assert.equal(result.inlineError, true);
  assert.match(result.error, /Неверные данные Telegram Mini App/);
});

test('Mini App missing initData returns retryable inline error path', async () => {
  let tryMiniAppLoginCalls = 0;
  let botFallbackCalls = 0;

  const result = await runTelegramAuthStart({
    telegramGlobal: { WebApp: { initData: '' } },
    tryMiniAppLogin: async () => {
      tryMiniAppLoginCalls += 1;
      return { success: true };
    },
    startBotFallback: async () => {
      botFallbackCalls += 1;
      return { mode: 'bot_fallback_started' };
    },
  });

  assert.equal(tryMiniAppLoginCalls, 0);
  assert.equal(botFallbackCalls, 0);
  assert.equal(result.mode, 'miniapp_error');
  assert.equal(result.retryable, true);
  assert.equal(result.error, TELEGRAM_AUTH_ERRORS.MINI_APP_INIT_DATA_MISSING);
});

test('External browser context still uses bot fallback', async () => {
  let botFallbackCalls = 0;

  const result = await runTelegramAuthStart({
    telegramGlobal: {},
    tryMiniAppLogin: async () => ({ success: true }),
    startBotFallback: async () => {
      botFallbackCalls += 1;
      return { mode: 'bot_fallback_started', token: 'abc' };
    },
  });

  assert.equal(botFallbackCalls, 1);
  assert.equal(result.mode, 'bot_fallback_started');
});
