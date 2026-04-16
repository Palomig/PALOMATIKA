<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private function auth(): array
    {
        return [
            'VAPID' => [
                'subject'    => config('services.vapid.subject'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];
    }

    /**
     * Send a push notification to a single subscription.
     * Payload is a JSON object: { title, body, url, icon? }
     */
    public function sendToSubscription(PushSubscription $sub, array $payload): void
    {
        $webPush     = new WebPush($this->auth());
        $subscription = Subscription::create([
            'endpoint'        => $sub->endpoint,
            'contentEncoding' => 'aesgcm',
            'keys'            => ['p256dh' => $sub->p256dh, 'auth' => $sub->auth],
        ]);

        $webPush->sendOneNotification($subscription, json_encode($payload));
    }

    /**
     * Send to all lesson_notify subscriptions for a user.
     */
    public function notifyUser(int $userId, array $payload): void
    {
        $subs = PushSubscription::where('user_id', $userId)
            ->where('lesson_notify', true)
            ->get();

        if ($subs->isEmpty()) {
            return;
        }

        $webPush = new WebPush($this->auth());

        foreach ($subs as $sub) {
            $subscription = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'contentEncoding' => 'aesgcm',
                'keys'            => ['p256dh' => $sub->p256dh, 'auth' => $sub->auth],
            ]);
            $webPush->queueNotification($subscription, json_encode($payload));
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getEndpoint())->delete();
            }
        }
    }
}
