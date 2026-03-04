export const TELEGRAM_AUTH_ERRORS = {
  MINI_APP_INIT_DATA_MISSING: 'Не удалось получить данные Telegram Mini App. Нажмите "Повторить" и попробуйте снова.',
  MINI_APP_LOGIN_FAILED: 'Не удалось войти через Telegram Mini App. Нажмите "Повторить" и попробуйте снова.',
};

export function getTelegramWebApp(telegramGlobal) {
  const tg = telegramGlobal ?? (typeof window !== 'undefined' ? window.Telegram : undefined);
  return tg && tg.WebApp ? tg.WebApp : null;
}

function normalizeMiniAppError(error) {
  if (typeof error === 'string' && error.trim() !== '') {
    return error;
  }

  if (error && typeof error.message === 'string' && error.message.trim() !== '') {
    return error.message;
  }

  return TELEGRAM_AUTH_ERRORS.MINI_APP_LOGIN_FAILED;
}

export async function runTelegramAuthStart({ telegramGlobal, tryMiniAppLogin, startBotFallback } = {}) {
  const webApp = getTelegramWebApp(telegramGlobal);

  if (webApp) {
    const initData = typeof webApp.initData === 'string' ? webApp.initData.trim() : '';

    if (!initData) {
      // Outside Telegram Mini App, Telegram.WebApp object may exist but initData is empty.
      // In that case, prefer bot fallback auth flow instead of hard error.
      if (typeof startBotFallback === 'function') {
        return await startBotFallback();
      }

      return {
        mode: 'miniapp_error',
        inlineError: true,
        retryable: true,
        error: TELEGRAM_AUTH_ERRORS.MINI_APP_INIT_DATA_MISSING,
      };
    }

    try {
      const miniAppResult = await (typeof tryMiniAppLogin === 'function'
        ? tryMiniAppLogin({ webApp, initData })
        : { success: false });

      if (miniAppResult && miniAppResult.success) {
        return {
          mode: 'miniapp_success',
          ...miniAppResult,
        };
      }

      // If Mini App auth fails, try bot fallback before surfacing error.
      if (typeof startBotFallback === 'function') {
        return await startBotFallback();
      }

      return {
        mode: 'miniapp_error',
        inlineError: true,
        retryable: true,
        error: normalizeMiniAppError(miniAppResult && miniAppResult.error),
      };
    } catch (error) {
      // Network/API issues on mini-app login: attempt bot fallback first.
      if (typeof startBotFallback === 'function') {
        return await startBotFallback();
      }

      return {
        mode: 'miniapp_error',
        inlineError: true,
        retryable: true,
        error: normalizeMiniAppError(error),
      };
    }
  }

  if (typeof startBotFallback === 'function') {
    return await startBotFallback();
  }

  return {
    mode: 'bot_fallback_unavailable',
  };
}
