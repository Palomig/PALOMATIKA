<?php

namespace App\Providers;

use App\Services\FriendInviteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Один экземпляр на запрос: полоска «Позови друга» кэшируется внутри.
        $this->app->scoped(FriendInviteService::class);
        // VPR services — grade-specific, bound with factory
        $this->app->bind(\App\Services\VprTaskDataService::class, function ($app, $params) {
            $grade = $params['grade'] ?? (auth()->user()?->grade_num ?? 5);
            return new \App\Services\VprTaskDataService((int) $grade);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production — hosting terminates SSL at the reverse proxy,
        // so Laravel sees HTTP. Without this, Secure cookies won't work.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Полоска «Позови друга» под плиткой УРОК — на всех трёх дашбордах ученика.
        // Дашбордам $friendStrip нужен ещё и чтобы переименовать старую плитку
        // «Пригласить друга» (ссылка на приложение), иначе рядом две разные акции.
        View::composer([
            'pwa.student.partials.friend-strip',
            'pwa.student.dashboard',
            'pwa.student.ege-home',
            'pwa.student.vpr-home',
        ], function ($view) {
            // Промо-строка не должна ронять главный экран ученика — например, в окне
            // между заливкой кода и миграцией при деплое. Ошибка → строки нет, в лог.
            try {
                $svc = app(FriendInviteService::class);
                $user = Auth::user();
                $strip = $svc->isEligible($user) ? $svc->strip($user) : null;
            } catch (\Throwable $e) {
                report($e);
                $strip = null;
            }
            $view->with('friendStrip', $strip);
        });
    }
}
