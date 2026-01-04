@extends('layouts.auth')

@section('title', 'Вход')

@section('content')
<div x-data="loginForm()">
    <h1 class="text-2xl font-bold text-white text-center mb-2">Вход в аккаунт</h1>
    <p class="text-gray-400 text-center mb-6">Продолжите подготовку к ОГЭ</p>

    <!-- Error message -->
    <div x-show="error" x-cloak class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-4">
        <span x-text="error"></span>
    </div>

    @if(session('error'))
    <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-4">
        {{ session('error') }}
    </div>
    @endif

    <!-- Login form -->
    <form @submit.prevent="submit">
        <div class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                <input
                    type="email"
                    id="email"
                    x-model="email"
                    class="w-full px-4 py-3 bg-dark border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-coral focus:border-transparent transition"
                    placeholder="your@email.com"
                    required
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Пароль</label>
                <input
                    type="password"
                    id="password"
                    x-model="password"
                    class="w-full px-4 py-3 bg-dark border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-coral focus:border-transparent transition"
                    placeholder="Ваш пароль"
                    required
                >
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
                class="w-full bg-coral text-white py-3.5 rounded-xl font-semibold hover:bg-coral-dark transition disabled:opacity-50 disabled:cursor-not-allowed"
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

    <!-- Divider -->
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-700"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-dark-light text-gray-500">или войдите через</span>
        </div>
    </div>

    <!-- Social login buttons -->
    <div class="space-y-3">
        <!-- VK Button -->
        <a href="{{ route('auth.social.redirect', 'vkontakte') }}" class="flex items-center justify-center w-full px-4 py-3 bg-dark border border-gray-700 rounded-xl hover:bg-dark-lighter transition">
            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="#4C75A3">
                <path d="M12.785 16.241s.288-.032.436-.194c.136-.148.132-.427.132-.427s-.02-1.304.587-1.496c.596-.19 1.362 1.259 2.175 1.815.615.42 1.082.328 1.082.328l2.175-.03s1.137-.07.598-.964c-.044-.073-.314-.661-1.618-1.869-1.366-1.263-1.182-1.059.462-3.245.999-1.33 1.399-2.141 1.274-2.489-.12-.332-.858-.244-.858-.244l-2.451.015s-.181-.025-.316.056c-.131.08-.216.265-.216.265s-.387 1.028-.902 1.902c-1.087 1.848-1.522 1.946-1.7 1.832-.415-.267-.311-1.073-.311-1.645 0-1.789.272-2.535-.53-2.729-.266-.064-.462-.107-1.144-.114-.874-.008-1.615.002-2.035.208-.279.137-.494.442-.363.459.163.022.53.099.726.364.253.343.244 1.113.244 1.113s.146 2.106-.339 2.368c-.333.18-.789-.188-1.769-1.868-.502-.86-.88-1.811-.88-1.811s-.072-.177-.202-.272c-.157-.114-.377-.151-.377-.151l-2.328.015s-.35.01-.478.161c-.114.134-.009.412-.009.412s1.82 4.258 3.882 6.401c1.888 1.964 4.032 1.835 4.032 1.835h.973z"/>
            </svg>
            <span class="text-gray-300 font-medium">Войти через ВКонтакте</span>
        </a>

        <!-- Telegram Widget -->
        @if(config('services.telegram.bot_username'))
        <div class="flex justify-center py-2">
            <script async src="https://telegram.org/js/telegram-widget.js?22"
                    data-telegram-login="{{ config('services.telegram.bot_username') }}"
                    data-size="large"
                    data-radius="12"
                    data-auth-url="{{ route('auth.telegram.callback') }}"
                    data-request-access="write">
            </script>
        </div>
        @else
        <div class="flex items-center justify-center w-full px-4 py-3 bg-dark border border-gray-700 rounded-xl text-gray-500">
            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
            </svg>
            <span class="text-sm">Telegram (не настроен)</span>
        </div>
        @endif
    </div>

    <!-- Register link -->
    <p class="text-center text-gray-400 mt-6">
        Нет аккаунта?
        <a href="{{ route('register') }}" class="text-coral font-medium hover:text-coral-light transition">Зарегистрироваться</a>
    </p>
</div>

<script>
function loginForm() {
    return {
        email: '',
        password: '',
        remember: false,
        loading: false,
        error: '',

        async submit() {
            this.loading = true;
            this.error = '';

            try {
                const response = await fetch('/api/auth/login', {
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
                    throw new Error(data.message || 'Ошибка входа');
                }

                localStorage.setItem('auth_token', data.token);
                window.location.href = '/dashboard';
            } catch (err) {
                this.error = err.message;
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
