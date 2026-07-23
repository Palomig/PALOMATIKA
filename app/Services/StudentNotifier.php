<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Отправка уведомлений ученику по доступным каналам.
 *
 * Фаза 1: телеграм-бот (только для oauth_provider='telegram'). In-app поп-ап
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
        if ($student->oauth_provider !== 'telegram' || empty($student->oauth_id)) {
            return false;
        }
        $token = (string) config('services.telegram.bot_token', '');
        if ($token === '') {
            return false;
        }

        $payload = [
            'chat_id'    => (string) $student->oauth_id,
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
                return true;
            }
            Log::warning('StudentNotifier telegram non-2xx', [
                'student_id' => $student->id,
                'status'     => $resp->status(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('StudentNotifier telegram failed', [
                'student_id' => $student->id,
                'error'      => $e->getMessage(),
            ]);
        }
        return false;
    }
}
