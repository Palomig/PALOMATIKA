<?php

namespace App\Http\Controllers;

use App\Models\StarTransaction;
use App\Models\UserGift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MiniAppBillingController extends Controller
{
    /**
     * Activate 7-day free trial (one-time).
     */
    public function activateTrial(Request $request)
    {
        $user = Auth::user();

        if ($user->tg_trial_used) {
            return response()->json(['error' => 'Пробный период уже использован'], 422);
        }

        if ($user->hasTgPremium()) {
            return response()->json(['error' => 'У вас уже есть Premium'], 422);
        }

        $user->update([
            'tg_premium_until' => now()->addDays(7),
            'tg_trial_used' => true,
        ]);

        return response()->json(['ok' => true, 'premium_until' => $user->tg_premium_until->toDateTimeString()]);
    }

    /**
     * Create Telegram Stars invoice link for premium purchase.
     */
    public function buyPremium(Request $request)
    {
        $user = Auth::user();
        $botToken = config('services.telegram.bot_token');

        if (!$botToken) {
            return response()->json(['error' => 'Bot not configured'], 503);
        }

        $payload = [
            'title' => 'Premium подписка',
            'description' => 'Доступ к ответам в базе заданий на 30 дней',
            'payload' => json_encode(['user_id' => $user->id, 'type' => 'premium_30d']),
            'currency' => 'XTR',
            'prices' => [['label' => 'Premium 30 дней', 'amount' => 100]],
        ];

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post("https://api.telegram.org/bot{$botToken}/createInvoiceLink", [
                'json' => $payload,
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!($data['ok'] ?? false)) {
                Log::error('Failed to create invoice link', ['response' => $data]);
                return response()->json(['error' => 'Не удалось создать счёт'], 500);
            }

            return response()->json(['invoice_url' => $data['result']]);
        } catch (\Exception $e) {
            Log::error('Invoice creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка создания счёта'], 500);
        }
    }

    /**
     * Request star balance payout (manual processing within 24h).
     */
    public function requestPayout(Request $request)
    {
        $user = Auth::user();

        if ($user->star_balance < 1) {
            return response()->json(['error' => 'Недостаточно звёзд для выплаты'], 422);
        }

        $pending = StarTransaction::where('user_id', $user->id)
            ->where('type', 'payout')
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return response()->json(['error' => 'У вас уже есть заявка на выплату'], 422);
        }

        $amount = $user->star_balance;

        StarTransaction::create([
            'user_id' => $user->id,
            'type' => 'payout',
            'amount' => -$amount,
            'status' => 'pending',
            'note' => "Заявка на выплату {$amount} ⭐",
        ]);

        $user->update(['star_balance' => 0]);

        return response()->json(['ok' => true, 'amount' => $amount]);
    }

    /**
     * Mark gift as seen.
     */
    public function giftSeen(Request $request)
    {
        $giftId = (int) $request->input('gift_id');
        UserGift::where('id', $giftId)
            ->where('user_id', Auth::id())
            ->whereNull('shown_at')
            ->update(['shown_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
