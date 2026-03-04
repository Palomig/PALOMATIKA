(function () {
  const TELEGRAM_AUTH_ERRORS = {
    MINI_APP_INIT_DATA_MISSING: 'Не удалось получить данные Telegram Mini App. Нажмите "Повторить" и попробуйте снова.',
    MINI_APP_LOGIN_FAILED: 'Не удалось войти через Telegram Mini App. Нажмите "Повторить" и попробуйте снова.',
  };

  function getTelegramWebApp(telegramGlobal) {
    const tg = telegramGlobal || (typeof window !== 'undefined' ? window.Telegram : undefined);
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

  async function runTelegramAuthStart(options) {
    const config = options || {};
    const webApp = getTelegramWebApp(config.telegramGlobal);

    if (webApp) {
      const initData = typeof webApp.initData === 'string' ? webApp.initData.trim() : '';

      if (!initData) {
        // Outside Telegram Mini App, Telegram.WebApp object may exist but initData is empty.
        // In that case, prefer bot fallback auth flow instead of hard error.
        if (typeof config.startBotFallback === 'function') {
          return await config.startBotFallback();
        }

        return {
          mode: 'miniapp_error',
          inlineError: true,
          retryable: true,
          error: TELEGRAM_AUTH_ERRORS.MINI_APP_INIT_DATA_MISSING,
        };
      }

      try {
        const miniAppResult = await (typeof config.tryMiniAppLogin === 'function'
          ? config.tryMiniAppLogin({ webApp: webApp, initData: initData })
          : { success: false });

        if (miniAppResult && miniAppResult.success) {
          return Object.assign({ mode: 'miniapp_success' }, miniAppResult);
        }

        return {
          mode: 'miniapp_error',
          inlineError: true,
          retryable: true,
          error: normalizeMiniAppError(miniAppResult && miniAppResult.error),
        };
      } catch (error) {
        return {
          mode: 'miniapp_error',
          inlineError: true,
          retryable: true,
          error: normalizeMiniAppError(error),
        };
      }
    }

    if (typeof config.startBotFallback === 'function') {
      return await config.startBotFallback();
    }

    return {
      mode: 'bot_fallback_unavailable',
    };
  }

  window.PalomatikaTelegramAuth = {
    TELEGRAM_AUTH_ERRORS: TELEGRAM_AUTH_ERRORS,
    getTelegramWebApp: getTelegramWebApp,
    runTelegramAuthStart: runTelegramAuthStart,
  };
})();
