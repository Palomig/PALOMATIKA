<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BugReportController extends Controller
{
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'url'          => 'required|string|max:500',
            'route_name'   => 'nullable|string|max:100',
            'description'  => 'nullable|string|max:2000',
            'user_agent'   => 'nullable|string|max:500',
            'screen_info'  => 'nullable|array',
            'js_errors'    => 'nullable|array',
            'page_context' => 'nullable|array',
        ]);

        $user = Auth::user();

        $report = BugReport::create([
            'user_id'      => $user?->id,
            'url'          => $data['url'],
            'route_name'   => $data['route_name'] ?? null,
            'description'  => $data['description'] ?? null,
            'user_agent'   => $data['user_agent'] ?? $request->userAgent(),
            'screen_info'  => $data['screen_info'] ?? null,
            'js_errors'    => $data['js_errors'] ?? null,
            'page_context' => $data['page_context'] ?? null,
            'ip'           => $request->ip(),
        ]);

        $this->notifyTelegram($report, $user);

        return response()->json(['ok' => true]);
    }

    private function notifyTelegram(BugReport $report, $user): void
    {
        $botToken  = config('services.telegram.bot_token');
        $adminChat = config('services.telegram.admin_chat_id');

        if (!$botToken || !$adminChat) {
            return;
        }

        $screen = $report->screen_info ?? [];
        $pwa    = ($screen['pwa'] ?? false) ? 'PWA' : 'браузер';
        $online = ($screen['online'] ?? true) ? 'онлайн' : 'офлайн';
        $conn   = $screen['connection'] ?? '?';
        $w      = $screen['screen_w'] ?? '?';
        $h      = $screen['screen_h'] ?? '?';

        $userName = $user ? "{$user->name} (ID:{$user->id}, класс {$user->grade_num})" : 'гость';

        $jsBlock = '';
        if (!empty($report->js_errors)) {
            $jsBlock = "\n⚠️ <b>JS ошибки:</b>\n" . htmlspecialchars(
                implode("\n", array_slice(array_map(
                    fn($e) => ($e['message'] ?? '') . ' @ ' . ($e['source'] ?? ''),
                    $report->js_errors
                ), 0, 3))
            );
        }

        $desc = $report->description
            ? "\n\n💬 <b>Описание:</b>\n" . htmlspecialchars($report->description)
            : '';

        $ctxBlock = '';
        if (!empty($report->page_context)) {
            $ctx = $report->page_context;
            $parts = [];
            if (isset($ctx['attempt_id']))          $parts[] = 'attempt #' . $ctx['attempt_id'];
            if (isset($ctx['current_task_number'])) $parts[] = 'задание ' . $ctx['current_task_number'];
            if (isset($ctx['topic_id']))            $parts[] = 'topic ' . $ctx['topic_id'];
            if (isset($ctx['task_key']))            $parts[] = $ctx['task_key'];
            if ($parts) {
                $ctxBlock = "\n📍 <b>Контекст:</b> " . htmlspecialchars(implode(' · ', $parts));
            }
        }

        $text = "🐛 <b>Баг-репорт #{$report->id}</b>\n"
            . "👤 {$userName}\n"
            . "📱 {$pwa} · {$online} · {$conn} · {$w}×{$h}\n"
            . "🌐 <code>" . htmlspecialchars($report->url) . "</code>\n"
            . "🕐 " . now()->setTimezone('Europe/Moscow')->format('d.m H:i')
            . $ctxBlock
            . $desc
            . $jsBlock;

        try {
            $client = new \GuzzleHttp\Client();
            $client->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'json'    => ['chat_id' => $adminChat, 'text' => $text, 'parse_mode' => 'HTML'],
                'timeout' => 5,
            ]);
        } catch (\Exception $e) {
            Log::warning('Bug report Telegram notify failed', ['error' => $e->getMessage()]);
        }
    }
}
