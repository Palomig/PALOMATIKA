@extends('layouts.auth')

@section('title', 'Вход')

@section('content')
<div x-data="telegramAutoLogin()" x-init="init()">
    {{-- Mini App auto-login screen (shown inside Telegram) --}}
    <template x-if="isMiniApp">
        <div class="text-center py-8">
            <div x-show="autoLoginLoading" class="space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-[#0088cc]/20 rounded-full mb-2">
                    <svg class="w-8 h-8 text-[#0088cc]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white">Входим через Telegram...</h2>
                <p class="text-gray-400 text-sm" x-text="autoLoginUserName ? 'Привет, ' + autoLoginUserName + '!' : 'Подождите немного'"></p>
                <svg class="animate-spin h-6 w-6 text-[#0088cc] mx-auto mt-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div x-show="autoLoginError" x-cloak class="space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-danger/20 rounded-full mb-2">
                    <svg class="w-8 h-8 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white">Не удалось войти</h2>
                <p class="text-danger text-sm" x-text="autoLoginError"></p>
                <div class="space-y-2 mt-4">
                    <button @click="retryAutoLogin()" class="w-full bg-[#0088cc] text-white py-3 rounded-button font-semibold hover:bg-[#0077b5] transition">
                        Попробовать снова
                    </button>
                    <button @click="isMiniApp = false" class="w-full bg-dark text-gray-300 py-3 rounded-button font-medium border border-gray-700 hover:bg-dark-lighter transition">
                        Войти другим способом
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Regular login form (shown in browser or as fallback) --}}
    <template x-if="!isMiniApp">
        <div x-data="loginForm()">
            <h1 class="text-2xl font-bold text-white text-center mb-2">Вход в аккаунт</h1>
            <p class="text-gray-400 text-center mb-6">Продолжите подготовку к экзаменам</p>

            {{-- Error message with shake --}}
            <div x-show="error" x-cloak
                 :class="{ 'animate-shake': shaking }"
                 class="bg-danger/10 border border-danger/20 text-danger px-4 py-3 rounded-card mb-4 text-sm">
                <span x-text="error"></span>
            </div>

            @if(session('error'))
            <div class="bg-danger/10 border border-danger/20 text-danger px-4 py-3 rounded-card mb-4 text-sm">
                {{ session('error') }}
            </div>
            @endif

            {{-- Login form --}}
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                        <input
                            type="email"
                            id="email"
                            x-model="email"
                            autocomplete="email"
                            class="w-full px-4 py-3 bg-dark border border-gray-700 rounded-input text-white placeholder-gray-500 focus:outline-none focus:border-coral focus:ring-1 focus:ring-coral/30 transition"
                            placeholder="your@email.com"
                            required
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">Пароль</label>
                        <div class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                x-model="password"
                                autocomplete="current-password"
                                class="w-full px-4 py-3 pr-12 bg-dark border border-gray-700 rounded-input text-white placeholder-gray-500 focus:outline-none focus:border-coral focus:ring-1 focus:ring-coral/30 transition"
                                placeholder="Ваш пароль"
                                required
                            >
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition"
                                    aria-label="Показать пароль">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" x-model="remember" class="w-4 h-4 text-coral bg-dark border-gray-700 rounded focus:ring-coral focus:ring-offset-dark">
                            <span class="ml-2 text-sm text-gray-400">Запомнить меня</span>
                        </label>
                        <a href="#" class="text-sm text-coral hover:text-coral-light transition">Забыли пароль?</a>
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full bg-coral text-white py-3.5 rounded-button font-semibold hover:bg-coral-dark transition shadow-glow-coral disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!loading">Войти</span>
                        <span x-show="loading" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Вход...
                        </span>
                    </button>
                </div>
            </form>

            {{-- Divider --}}
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-dark-light text-gray-500">или войдите через</span>
                </div>
            </div>

            {{-- Social login buttons --}}
            <div class="space-y-3">
                <a href="{{ route('auth.social.redirect', 'vkontakte') }}" class="flex items-center justify-center w-full px-4 py-3 bg-dark border border-gray-700 rounded-button hover:bg-dark-lighter transition">
                    <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="#4C75A3">
                        <path d="M12.785 16.241s.288-.032.436-.194c.136-.148.132-.427.132-.427s-.02-1.304.587-1.496c.596-.19 1.362 1.259 2.175 1.815.615.42 1.082.328 1.082.328l2.175-.03s1.137-.07.598-.964c-.044-.073-.314-.661-1.618-1.869-1.366-1.263-1.182-1.059.462-3.245.999-1.33 1.399-2.141 1.274-2.489-.12-.332-.858-.244-.858-.244l-2.451.015s-.181-.025-.316.056c-.131.08-.216.265-.216.265s-.387 1.028-.902 1.902c-1.087 1.848-1.522 1.946-1.7 1.832-.415-.267-.311-1.073-.311-1.645 0-1.789.272-2.535-.53-2.729-.266-.064-.462-.107-1.144-.114-.874-.008-1.615.002-2.035.208-.279.137-.494.442-.363.459.163.022.53.099.726.364.253.343.244 1.113.244 1.113s.146 2.106-.339 2.368c-.333.18-.789-.188-1.769-1.868-.502-.86-.88-1.811-.88-1.811s-.072-.177-.202-.272c-.157-.114-.377-.151-.377-.151l-2.328.015s-.35.01-.478.161c-.114.134-.009.412-.009.412s1.82 4.258 3.882 6.401c1.888 1.964 4.032 1.835 4.032 1.835h.973z"/>
                    </svg>
                    <span class="text-gray-300 font-medium">Войти через ВКонтакте</span>
                </a>

                @if(config('services.telegram.bot_username'))
                <div x-data="telegramAuth()">
                    <button
                        @click="startAuth"
                        :disabled="loading"
                        class="flex items-center justify-center w-full px-4 py-3 bg-[#0088cc] border border-[#0088cc] rounded-button hover:bg-[#0077b5] transition disabled:opacity-50"
                    >
                        <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="white">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                        <span x-show="!loading && !waiting" class="text-white font-medium">Войти через Telegram</span>
                        <span x-show="loading" class="text-white font-medium">Подготовка...</span>
                        <span x-show="waiting" class="text-white font-medium">Ожидание подтверждения...</span>
                    </button>
                    <p x-show="waiting" class="text-center text-gray-400 text-sm mt-2">
                        Откройте Telegram и нажмите "Start" в боте
                    </p>
                    <div x-show="error" class="text-center mt-2">
                        <p class="text-danger text-sm" x-text="error"></p>
                        <button
                            type="button"
                            @click="startAuth"
                            class="mt-2 text-xs text-gray-300 underline hover:text-white transition"
                        >
                            Повторить
                        </button>
                    </div>
                </div>
                @else
                <div class="flex items-center justify-center w-full px-4 py-3 bg-dark border border-gray-700 rounded-button text-gray-500">
                    <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                    </svg>
                    <span class="text-sm">Telegram (не настроен)</span>
                </div>
                @endif
            </div>

            {{-- Register link --}}
            <p class="text-center text-gray-400 mt-6">
                Нет аккаунта?
                <a href="{{ route('register') }}" class="text-coral font-medium hover:text-coral-light transition">Зарегистрироваться</a>
            </p>
        </div>
    </template>
</div>

<script src="/js/telegram-auth.js?v={{ @filemtime(public_path('js/telegram-auth.js')) }}"></script>
<script>
function telegramAutoLogin() {
    return {
        isMiniApp: false,
        autoLoginLoading: true,
        autoLoginError: '',
        autoLoginUserName: '',

        init() {
            const webApp = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
            const initData = webApp && typeof webApp.initData === 'string' ? webApp.initData.trim() : '';

            if (webApp && initData) {
                this.isMiniApp = true;

                // Extract user name for greeting
                if (webApp.initDataUnsafe && webApp.initDataUnsafe.user) {
                    this.autoLoginUserName = webApp.initDataUnsafe.user.first_name || '';
                }

                // Signal Telegram that Mini App is ready
                if (typeof webApp.ready === 'function') {
                    webApp.ready();
                }

                // Auto-login immediately
                this.performAutoLogin(webApp, initData);
            }
        },

        async performAutoLogin(webApp, initData) {
            this.autoLoginLoading = true;
            this.autoLoginError = '';

            try {
                const response = await fetch('/api/auth/telegram/webapp-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        initData,
                        initDataUnsafe: webApp.initDataUnsafe || null,
                        startParam: (webApp.initDataUnsafe && webApp.initDataUnsafe.start_param) ? webApp.initDataUnsafe.start_param : null,
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Ошибка входа через Telegram Mini App');
                }

                window.location.href = data.redirect_to || '/dashboard';
            } catch (err) {
                this.autoLoginLoading = false;
                this.autoLoginError = err.message || 'Не удалось войти автоматически';
            }
        },

        retryAutoLogin() {
            const webApp = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
            const initData = webApp && typeof webApp.initData === 'string' ? webApp.initData.trim() : '';

            if (webApp && initData) {
                this.performAutoLogin(webApp, initData);
            } else {
                this.autoLoginError = 'Данные Telegram недоступны. Попробуйте открыть приложение заново.';
            }
        }
    }
}

function loginForm() {
    return {
        email: '',
        password: '',
        remember: false,
        loading: false,
        error: '',
        shaking: false,
        showPassword: false,

        async submit() {
            this.loading = true;
            this.error = '';

            try {
                const response = await fetch('/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        email: this.email,
                        password: this.password,
                        remember: this.remember
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Неверный email или пароль');
                }

                if (data.token) {
                    localStorage.setItem('auth_token', data.token);
                }
                window.location.href = data.redirect_to || '/dashboard';
            } catch (err) {
                this.error = err.message;
                this.shaking = true;
                setTimeout(() => this.shaking = false, 500);
            } finally {
                this.loading = false;
            }
        }
    }
}

function telegramAuth() {
    return {
        loading: false,
        waiting: false,
        error: '',
        token: null,
        traceId: null,
        pollInterval: null,

        async startAuth() {
            this.loading = true;
            this.error = '';
            this.waiting = false;
            this.traceId = this.generateTraceId();

            try {
                const authHelper = window.PalomatikaTelegramAuth || {};
                const result = await Promise.race([
                    authHelper.runTelegramAuthStart({
                        telegramGlobal: window.Telegram,
                        tryMiniAppLogin: ({ webApp, initData }) => this.tryMiniAppLogin(webApp, initData),
                        startBotFallback: () => this.startBotFallbackAuth(),
                    }),
                    new Promise(resolve => setTimeout(() => resolve({ mode: 'auth_timeout' }), 8000)),
                ]);

                if (result && result.mode === 'miniapp_success') {
                    return;
                }

                if (result && result.mode === 'miniapp_error') {
                    this.error = result.error || 'Ошибка входа через Telegram Mini App';
                    this.loading = false;
                    this.waiting = false;
                    return;
                }

                if (result && result.mode === 'auth_timeout') {
                    await this.startBotFallbackAuth();
                    return;
                }
            } catch (err) {
                this.error = err.message;
                this.loading = false;
            }
        },

        getTelegramWebApp() {
            return window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
        },

        async tryMiniAppLogin(webApp, initData) {
            try {
                if (typeof webApp.ready === 'function') {
                    webApp.ready();
                }

                const response = await fetch('/api/auth/telegram/webapp-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content
                    },
                    body: JSON.stringify({
                        initData,
                        initDataUnsafe: webApp.initDataUnsafe || null,
                        startParam: (webApp.initDataUnsafe && webApp.initDataUnsafe.start_param) ? webApp.initDataUnsafe.start_param : null,
                        trace_id: this.traceId,
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Ошибка входа через Telegram Mini App');
                }

                window.location.href = data.redirect_to || '/dashboard';
                return { success: true };
            } catch (err) {
                return {
                    success: false,
                    error: err.message || 'Ошибка входа через Telegram Mini App',
                };
            }
        },

        async startBotFallbackAuth() {
            const webApp = this.getTelegramWebApp();
            const startParam = (webApp && webApp.initDataUnsafe && webApp.initDataUnsafe.start_param)
                ? webApp.initDataUnsafe.start_param
                : null;

            const response = await fetch('/api/telegram/generate-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    startParam,
                    trace_id: this.traceId,
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Ошибка генерации токена');
            }

            this.token = data.token;
            this.loading = false;
            this.waiting = true;

            const ua = navigator.userAgent || '';
            const isMobile = /iPhone|iPad|iPod|Android/i.test(ua);
            if (isMobile) {
                window.location.href = data.deep_link;
            } else {
                window.open(data.deep_link, '_blank');
            }
            this.startPolling();

            return {
                mode: 'bot_fallback_started',
                token: data.token,
            };
        },

        startPolling() {
            let attempts = 0;
            const maxAttempts = 150;

            this.pollInterval = setInterval(async () => {
                attempts++;

                if (attempts > maxAttempts) {
                    this.stopPolling();
                    this.waiting = false;
                    this.error = 'Время ожидания истекло. Попробуйте снова.';
                    return;
                }

                try {
                    const response = await fetch(`/api/telegram/check-token/${this.token}?trace_id=${encodeURIComponent(this.traceId || '')}`);
                    const data = await response.json();

                    if (data.status === 'authenticated') {
                        this.stopPolling();
                        window.location.href = data.login_url;
                    } else if (data.status === 'expired' || data.status === 'not_found') {
                        this.stopPolling();
                        this.waiting = false;
                        this.error = 'Сессия истекла. Попробуйте снова.';
                    }
                } catch (err) {
                    console.error('Polling error:', err);
                }
            }, 2000);
        },

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        generateTraceId() {
            return `tg-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
        }
    }
}
</script>
@endsection
