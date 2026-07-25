<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Отправка уведомлений ученику по доступным каналам.
 *
 * Фаза 1: телеграм-бот (нужен привязанный `telegram_chat_id`). In-app поп-ап
 * читает состояние из БД при заходе — отдельной отправки не требует.
 * Фаза 2: web push (метод-заглушка появится позже).
 */
class StudentNotifier
{
    /**
     * @return bool доставлено ли (хотя бы одним каналом)
     */
    public function notify(User $student, string $text, ?string $url = null): bool
    {
        return $this->sendTelegram($student, $text, $url);
    }

    private function sendTelegram(User $student, string $text, ?string $url): bool
    {
        // Пишем только на настоящий chat_id: oauth_id у OIDC-входа — псевдоним,
        // Telegram на него отвечает «chat not found».
        $chatId = $student->telegram_chat_id;
        if (!$chatId) {
            return false;
        }

        $token = (string) config('services.telegram.bot_token', '');
        if ($token === '') {
            Log::error('StudentNotifier: telegram bot token not configured');
            return false;
        }

        $payload = [
            'chat_id'    => (string) $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($url) {
            $payload['reply_markup'] = [
                'inline_keyboard' => [[['text' => 'Открыть', 'url' => $url]]],
            ];
        }

        try {
            $resp = Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
            if ($resp->successful()) {
                if ($student->telegram_blocked_at !== null) {
                    $student->update(['telegram_blocked_at' => null]);
                }
                return true;
            }

            // 403 = ученик заблокировал бота или не нажимал Start. Помечаем,
            // чтобы учитель видел «не доставлено», а не гадал.
            if ($resp->status() === 403) {
                $student->update(['telegram_blocked_at' => now()]);
            }

            // Уровень error, а не warning: на проде LOG_LEVEL=error, и warning
            // просто исчезал — из-за этого недоставку не видел никто.
            Log::error('StudentNotifier telegram non-2xx', [
                'student_id'  => $student->id,
                'status'      => $resp->status(),
                'description' => (string) $resp->json('description', ''),
            ]);
        } catch (\Throwable $e) {
            Log::error('StudentNotifier telegram failed', [
                'student_id' => $student->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return false;
    }
}
