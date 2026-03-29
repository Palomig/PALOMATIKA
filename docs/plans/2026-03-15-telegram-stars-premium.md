# Telegram Stars Premium Subscription — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add premium subscription (100 Telegram Stars/month, 7-day trial) that unlocks answer visibility in the mini app task browser, with referral commission system and student profile page.

**Architecture:** New fields on `users` table (`tg_premium_until`, `tg_trial_used`, `star_balance`) + new `star_transactions` table for audit trail. Payment via Telegram Bot API `createInvoiceLink`. Webhook handler in existing `TelegramBotAuthController::webhook()` processes `pre_checkout_query` and `successful_payment`. Referrer gets `partner_commission_percent`% (default 30%) credited to `star_balance`. Profile page at `/tg/profile` shows balance, transactions, payout request.

**Tech Stack:** Laravel 10, MySQL, Alpine.js (CDN), Telegram Bot API (Stars payments)

---

## Task 1: Database Migration

**Files:**
- Create: `database/migrations/2026_03_15_100000_add_telegram_premium_fields.php`

**Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('tg_premium_until')->nullable()->after('trial_ends_at');
            $table->boolean('tg_trial_used')->default(false)->after('tg_premium_until');
            $table->integer('star_balance')->default(0)->after('tg_trial_used');
        });

        Schema::create('star_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase', 'referral_bonus', 'payout']);
            $table->integer('amount'); // positive for income, negative for payout
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('telegram_charge_id')->nullable();
            $table->enum('status', ['completed', 'pending'])->default('completed');
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('star_transactions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tg_premium_until', 'tg_trial_used', 'star_balance']);
        });
    }
};
```

**Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration successful, new columns on `users`, new table `star_transactions`.

**Step 3: Commit**

```bash
git add database/migrations/2026_03_15_100000_add_telegram_premium_fields.php
git commit -m "feat: add telegram premium fields migration"
```

---

## Task 2: StarTransaction Model + User Model Updates

**Files:**
- Create: `app/Models/StarTransaction.php`
- Modify: `app/Models/User.php`

**Step 1: Create StarTransaction model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StarTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'related_user_id',
        'telegram_charge_id',
        'status',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }
}
```

**Step 2: Update User model**

Add to `$fillable` array (after `'trial_ends_at'`):
```php
'tg_premium_until',
'tg_trial_used',
'star_balance',
```

Add to `$casts` array:
```php
'tg_premium_until' => 'datetime',
'tg_trial_used' => 'boolean',
```

Add relationship and helper methods after `getSubscriptionPlanLabel()`:
```php
public function starTransactions(): HasMany
{
    return $this->hasMany(StarTransaction::class);
}

public function hasTgPremium(): bool
{
    return $this->tg_premium_until && $this->tg_premium_until->isFuture();
}
```

**Step 3: Commit**

```bash
git add app/Models/StarTransaction.php app/Models/User.php
git commit -m "feat: add StarTransaction model and User premium helpers"
```

---

## Task 3: Premium Purchase & Trial Endpoints

**Files:**
- Modify: `app/Http/Controllers/MiniAppController.php`
- Modify: `routes/web.php`

**Step 1: Add premium endpoints to MiniAppController**

Add these methods to `MiniAppController`:

```php
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

    // Check for existing pending payout
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
```

Add import at top of controller:
```php
use App\Models\StarTransaction;
```

**Step 2: Add routes in `routes/web.php`**

Inside the `/tg` authenticated + onboarding-complete group (after the history route at line ~437), add:

```php
Route::get('/profile', [MiniAppController::class, 'profile'])->name('miniapp.profile');
Route::post('/premium/trial', [MiniAppController::class, 'activateTrial'])->name('miniapp.premium.trial');
Route::post('/premium/buy', [MiniAppController::class, 'buyPremium'])->name('miniapp.premium.buy');
Route::post('/premium/payout', [MiniAppController::class, 'requestPayout'])->name('miniapp.premium.payout');
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/MiniAppController.php routes/web.php
git commit -m "feat: add premium purchase, trial, and payout endpoints"
```

---

## Task 4: Webhook Handler for Telegram Stars Payments

**Files:**
- Modify: `app/Http/Controllers/Auth/TelegramBotAuthController.php`

**Step 1: Add payment webhook handlers**

Add import at top:
```php
use App\Models\StarTransaction;
```

In the `webhook()` method, add two new blocks **before** the `callback_query` handler (line ~449):

```php
// Handle pre-checkout query (must answer within 10 seconds)
if (isset($update['pre_checkout_query'])) {
    $this->handlePreCheckoutQuery($update['pre_checkout_query']);
    return response()->json(['ok' => true]);
}

// Handle successful payment
if (isset($update['message']['successful_payment'])) {
    $this->handleSuccessfulPayment($update['message']);
    return response()->json(['ok' => true]);
}
```

Add the two private methods:

```php
/**
 * Answer pre-checkout query — must respond within 10s or payment fails.
 */
private function handlePreCheckoutQuery(array $query): void
{
    $botToken = config('services.telegram.bot_token');
    if (!$botToken) return;

    $payload = json_decode($query['invoice_payload'] ?? '{}', true);
    $userId = $payload['user_id'] ?? null;

    // Basic validation: user exists
    $ok = $userId && User::where('id', $userId)->exists();

    $url = "https://api.telegram.org/bot{$botToken}/answerPreCheckoutQuery";
    $body = ['pre_checkout_query_id' => $query['id'], 'ok' => $ok];
    if (!$ok) {
        $body['error_message'] = 'Пользователь не найден';
    }

    try {
        (new \GuzzleHttp\Client())->post($url, ['json' => $body, 'timeout' => 5]);
    } catch (\Exception $e) {
        \Log::error('answerPreCheckoutQuery failed', ['error' => $e->getMessage()]);
    }
}

/**
 * Process successful Telegram Stars payment.
 */
private function handleSuccessfulPayment(array $message): void
{
    $payment = $message['successful_payment'] ?? [];
    $chargeId = $payment['telegram_payment_charge_id'] ?? '';
    $payload = json_decode($payment['invoice_payload'] ?? '{}', true);
    $userId = $payload['user_id'] ?? null;

    if (!$userId || ($payload['type'] ?? '') !== 'premium_30d') {
        \Log::warning('Unknown payment payload', ['payload' => $payload]);
        return;
    }

    // Prevent duplicate processing
    if ($chargeId && StarTransaction::where('telegram_charge_id', $chargeId)->exists()) {
        \Log::info('Duplicate payment skipped', ['charge_id' => $chargeId]);
        return;
    }

    $user = User::find($userId);
    if (!$user) {
        \Log::error('Payment for non-existent user', ['user_id' => $userId]);
        return;
    }

    // Extend premium (stack on top of existing if still active)
    $baseDate = $user->hasTgPremium() ? $user->tg_premium_until : now();
    $user->update(['tg_premium_until' => $baseDate->copy()->addDays(30)]);

    // Log purchase transaction
    StarTransaction::create([
        'user_id' => $user->id,
        'type' => 'purchase',
        'amount' => -100,
        'telegram_charge_id' => $chargeId ?: null,
        'note' => 'Premium 30 дней',
    ]);

    // Referral bonus
    if ($user->referred_by_user_id) {
        $referrer = User::find($user->referred_by_user_id);
        if ($referrer) {
            $commissionPercent = $referrer->partner_commission_percent ?? 30;
            $bonus = (int) round(100 * $commissionPercent / 100);
            if ($bonus > 0) {
                $referrer->increment('star_balance', $bonus);

                StarTransaction::create([
                    'user_id' => $referrer->id,
                    'type' => 'referral_bonus',
                    'amount' => $bonus,
                    'related_user_id' => $user->id,
                    'note' => "Реферальный бонус {$commissionPercent}% от покупки",
                ]);
            }
        }
    }

    \Log::info('Premium activated via Stars', [
        'user_id' => $user->id,
        'charge_id' => $chargeId,
        'premium_until' => $user->tg_premium_until,
    ]);
}
```

**Step 2: Commit**

```bash
git add app/Http/Controllers/Auth/TelegramBotAuthController.php
git commit -m "feat: handle Telegram Stars payment webhook"
```

---

## Task 5: Pass Answer Data + isPremium to Task Browser Views

**Files:**
- Modify: `app/Http/Controllers/MiniAppController.php`

**Step 1: Update `tasksPart1()` method**

In `tasksPart1()` (line ~463), add answer to each task array. Change the task building inside the regular tasks loop (line ~517):

Replace:
```php
$tasks[] = [
    'id'         => $t['id'] ?? null,
    'text'       => $text,
    'expression' => $expression !== '' ? $expression : null,
    'svg'        => $t['svg'] ?? null,
    'image'      => $t['image'] ?? null,
    'options'    => $t['options'] ?? null,
    'question'   => $question !== '' ? $question : null,
];
```

With:
```php
$tasks[] = [
    'id'         => $t['id'] ?? null,
    'text'       => $text,
    'expression' => $expression !== '' ? $expression : null,
    'svg'        => $t['svg'] ?? null,
    'image'      => $t['image'] ?? null,
    'options'    => $t['options'] ?? null,
    'question'   => $question !== '' ? $question : null,
    'answer'     => $t['answer'] ?? null,
];
```

Do the same for the statements loop (line ~484), add `'answer' => $s['answer'] ?? null,` to the task array.

At the end of the method, add `isPremium` to the view data (line ~543):

Replace:
```php
return view('miniapp.tasks-part1', [
    'topicIds'      => $topicIds,
    'selectedTopic' => $selected,
    'zadaniya'      => $zadaniya,
    'taskCount'     => $taskCount,
]);
```

With:
```php
return view('miniapp.tasks-part1', [
    'topicIds'      => $topicIds,
    'selectedTopic' => $selected,
    'zadaniya'      => $zadaniya,
    'taskCount'     => $taskCount,
    'isPremium'     => Auth::user()->hasTgPremium(),
    'trialUsed'     => (bool) Auth::user()->tg_trial_used,
]);
```

**Step 2: Update `part2()` method**

Add `'answer' => $t['answer'] ?? null,` to the task array (line ~444).

Add to view data (line ~464):
```php
'isPremium'     => Auth::user()->hasTgPremium(),
'trialUsed'     => (bool) Auth::user()->tg_trial_used,
```

**Step 3: Update `newTasks()` method**

Add `'answer' => $task['answer'] ?? null,` in both places where task arrays are built:
- Line ~351 (flat list): add to the array
- Line ~385 (grouped/topic 10): add to the array

Add to the view return (line ~407):
```php
'isPremium'     => Auth::user()->hasTgPremium(),
'trialUsed'     => (bool) Auth::user()->tg_trial_used,
```

**Step 4: Commit**

```bash
git add app/Http/Controllers/MiniAppController.php
git commit -m "feat: pass answer data and premium status to task browser views"
```

---

## Task 6: Premium Answer UI in tasks-part1.blade.php

**Files:**
- Modify: `resources/views/miniapp/tasks-part1.blade.php`

**Step 1: Add answer styles**

Add to the `@push('styles')` block (before the closing `</style>`):

```css
.answer-row {
  margin-top: 8px; display: flex; align-items: center; gap: 8px;
}
.answer-label {
  font-size: 10px; font-weight: 800; letter-spacing: .06em;
  text-transform: uppercase; color: var(--muted); white-space: nowrap;
}
.answer-value {
  font-family: var(--display); font-size: 14px; color: var(--green);
}
.answer-blur {
  filter: blur(6px); user-select: none; pointer-events: none;
  color: var(--text); font-family: var(--display); font-size: 14px;
}
.premium-cta {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 700; color: var(--purple);
  cursor: pointer; white-space: nowrap;
}

/* Premium modal */
.pm-overlay {
  position: fixed; inset: 0; z-index: 100;
  background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
  display: flex; align-items: flex-end; justify-content: center;
}
.pm-sheet {
  background: var(--bg); border-radius: 20px 20px 0 0;
  width: 100%; max-width: 420px; padding: 24px 20px 32px;
}
.pm-handle {
  width: 36px; height: 4px; background: var(--border);
  border-radius: 2px; margin: 0 auto 16px;
}
.pm-title {
  font-family: var(--display); font-size: 20px; color: var(--text);
  text-align: center; margin-bottom: 8px;
}
.pm-desc {
  font-size: 13px; color: var(--muted); text-align: center;
  line-height: 1.5; margin-bottom: 20px;
}
.pm-price {
  font-family: var(--display); font-size: 28px; color: var(--text);
  text-align: center; margin-bottom: 20px;
}
.pm-price small { font-size: 14px; color: var(--muted); }
.pm-btn {
  display: block; width: 100%; padding: 16px;
  border: none; border-radius: 14px;
  font-family: var(--display); font-size: 15px;
  cursor: pointer; text-align: center; margin-bottom: 10px;
}
.pm-btn-primary { background: var(--purple); color: #fff; }
.pm-btn-primary:active { filter: brightness(0.9); }
.pm-btn-trial { background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple); }
.pm-btn-trial:active { filter: brightness(0.9); }
.pm-cancel {
  display: block; width: 100%; padding: 14px;
  background: none; border: none; color: var(--muted);
  font-size: 14px; font-weight: 700; cursor: pointer;
}
```

**Step 2: Wrap section body in Alpine.js component and add answer display**

Replace `@section('body')` content with:

```blade
@section('body')
<div class="page task-render-scope" x-data="taskBrowser()">
  <a href="/tg/dashboard" class="back-btn">‹</a>

  <div class="hero" style="opacity:0; animation: fadeUp 0.3s ease 0.04s forwards;">
    <div class="hero-title">1я часть ОГЭ</div>
    <div class="hero-sub">задания 6–19 · {{ $taskCount }} заданий</div>
  </div>

  <div class="sec-label">Выбери задание</div>
  <div class="topics-row">
    @foreach($topicIds as $tid)
      <a class="topic-pill {{ $selectedTopic === $tid ? 'active' : '' }}"
         href="{{ url('/tg/tasks-part1?topic=' . (int)$tid) }}">
        {{ (int)$tid }}
      </a>
    @endforeach
  </div>

  <div class="sec-label" style="margin-top:14px;">Задание {{ (int)$selectedTopic }}</div>

  <div class="task-list">
    @forelse($zadaniya as $group)
      <details class="spoiler" {{ $loop->first ? 'open' : '' }}>
        <summary>{{ $group['title'] }} <span style="font-size:11px;color:var(--muted);font-weight:400;">({{ count($group['tasks']) }})</span></summary>
        <div class="spoiler-body">
          @foreach($group['tasks'] as $task)
            <div class="task-item">
              @php
                $svg = is_string($task['svg'] ?? null) ? $task['svg'] : '';
                $image = is_string($task['image'] ?? null) ? $task['image'] : '';
              @endphp

              @if($svg !== '')
                <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:#0a1628; padding:8px;">
                  {!! $svg !!}
                </div>
              @elseif($image !== '')
                @if(\Illuminate\Support\Str::startsWith($image, '<svg'))
                  <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:#0a1628; padding:8px;">
                    {!! $image !!}
                  </div>
                @else
                  <img src="{{ str_starts_with($image, 'http') || str_starts_with($image, '/') ? $image : asset('images/tasks/' . $selectedTopic . '/' . ltrim($image, '/')) }}"
                       alt="" style="display:block;max-width:100%;height:auto;margin-bottom:10px;border:1px solid var(--border);border-radius:10px;background:#fff;padding:4px;" loading="lazy">
                @endif
              @endif

              @if(!empty($task['question']))
                <div class="task-item-text" style="margin-bottom:6px; color:var(--muted); font-size:12px;">{{ $task['question'] }}</div>
              @endif

              @if($task['text'] !== '')
                <div class="task-item-text">{!! nl2br(e($task['text'])) !!}</div>
              @elseif(!empty($task['expression']))
                <div class="task-item-text" style="font-size:15px;">$${{ $task['expression'] }}$$</div>
              @endif

              @if(!empty($task['options']) && is_array($task['options']))
                <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px;">
                  @foreach($task['options'] as $opt)
                    <span style="padding:4px 10px; border:1px solid var(--border); border-radius:8px; font-size:12px; color:var(--muted);">{{ is_array($opt) ? ($opt['label'] ?? $opt['text'] ?? json_encode($opt)) : $opt }}</span>
                  @endforeach
                </div>
              @endif

              {{-- ANSWER ROW --}}
              @if(!empty($task['answer']))
                <div class="answer-row">
                  <span class="answer-label">Ответ:</span>
                  @if($isPremium)
                    <span class="answer-value">{{ $task['answer'] }}</span>
                  @else
                    <span class="answer-blur">{{ $task['answer'] }}</span>
                    <span class="premium-cta" @click="showPremium = true">🔓 Premium</span>
                  @endif
                </div>
              @endif

              @if(!empty($task['id']))
                <div class="task-item-meta">#{{ $task['id'] }}</div>
              @endif
            </div>
          @endforeach
        </div>
      </details>
    @empty
      <div class="task-item">
        <div class="task-item-text">Для этого задания пока нет заданий в статусе production.</div>
      </div>
    @endforelse
  </div>

  {{-- PREMIUM MODAL --}}
  <template x-if="showPremium">
    <div class="pm-overlay" @click.self="showPremium = false">
      <div class="pm-sheet">
        <div class="pm-handle"></div>
        <div class="pm-title">⭐ Premium</div>
        <div class="pm-desc">Открой ответы ко всем заданиям в базе.<br>Подписка на 30 дней.</div>
        <div class="pm-price">100 ⭐ <small>/ мес</small></div>
        <button class="pm-btn pm-btn-primary" @click="buyPremium()" :disabled="buying" x-text="buying ? 'Загрузка...' : 'Купить за 100 ⭐'"></button>
        @if(!$trialUsed)
        <button class="pm-btn pm-btn-trial" @click="activateTrial()" :disabled="buying" x-text="trialActivating ? 'Активация...' : '🎁 7 дней бесплатно'"></button>
        @endif
        <button class="pm-cancel" @click="showPremium = false">Отмена</button>
      </div>
    </div>
  </template>
</div>
@endsection
```

**Step 3: Add Alpine.js component in scripts**

Replace the existing `@push('scripts')` block with:

```blade
@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof renderMathInElement === 'function') {
      document.querySelectorAll('.task-render-scope').forEach(function (el) {
        renderMathInElement(el, {
          delimiters: [
            { left: '$$', right: '$$', display: true },
            { left: '\\[', right: '\\]', display: true },
            { left: '\\(', right: '\\)', display: false },
            { left: '$', right: '$', display: false }
          ],
          throwOnError: false
        });
      });
    }
  });

  function taskBrowser() {
    return {
      showPremium: false,
      buying: false,
      trialActivating: false,

      async buyPremium() {
        if (this.buying) return;
        this.buying = true;
        try {
          const res = await window.fetchPost('/tg/premium/buy');
          const data = await res.json();
          if (data.invoice_url) {
            const tg = window.Telegram?.WebApp;
            if (tg && tg.openInvoice) {
              tg.openInvoice(data.invoice_url, (status) => {
                if (status === 'paid') {
                  window.location.reload();
                }
                this.buying = false;
              });
            } else {
              window.open(data.invoice_url, '_blank');
              this.buying = false;
            }
          } else {
            alert(data.error || 'Ошибка');
            this.buying = false;
          }
        } catch (e) {
          alert('Ошибка соединения');
          this.buying = false;
        }
      },

      async activateTrial() {
        if (this.trialActivating) return;
        this.trialActivating = true;
        try {
          const res = await window.fetchPost('/tg/premium/trial');
          const data = await res.json();
          if (data.ok) {
            window.location.reload();
          } else {
            alert(data.error || 'Ошибка');
            this.trialActivating = false;
          }
        } catch (e) {
          alert('Ошибка соединения');
          this.trialActivating = false;
        }
      },
    };
  }
</script>
@endpush
```

**Step 4: Commit**

```bash
git add resources/views/miniapp/tasks-part1.blade.php
git commit -m "feat: show blurred/premium answers in Part 1 task browser"
```

---

## Task 7: Premium Answer UI in part2.blade.php

**Files:**
- Modify: `resources/views/miniapp/part2.blade.php`

**Step 1: Add the same answer + premium styles**

Add to `@push('styles')` (before `@endpush`):
```css
.answer-row {
  margin-top: 8px; display: flex; align-items: center; gap: 8px;
}
.answer-label {
  font-size: 10px; font-weight: 800; letter-spacing: .06em;
  text-transform: uppercase; color: var(--muted); white-space: nowrap;
}
.answer-value {
  font-family: var(--display); font-size: 14px; color: var(--green);
}
.answer-blur {
  filter: blur(6px); user-select: none; pointer-events: none;
  color: var(--text); font-family: var(--display); font-size: 14px;
}
.premium-cta {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 700; color: var(--purple);
  cursor: pointer; white-space: nowrap;
}
.pm-overlay {
  position: fixed; inset: 0; z-index: 100;
  background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
  display: flex; align-items: flex-end; justify-content: center;
}
.pm-sheet {
  background: var(--bg); border-radius: 20px 20px 0 0;
  width: 100%; max-width: 420px; padding: 24px 20px 32px;
}
.pm-handle { width: 36px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 16px; }
.pm-title { font-family: var(--display); font-size: 20px; color: var(--text); text-align: center; margin-bottom: 8px; }
.pm-desc { font-size: 13px; color: var(--muted); text-align: center; line-height: 1.5; margin-bottom: 20px; }
.pm-price { font-family: var(--display); font-size: 28px; color: var(--text); text-align: center; margin-bottom: 20px; }
.pm-price small { font-size: 14px; color: var(--muted); }
.pm-btn { display: block; width: 100%; padding: 16px; border: none; border-radius: 14px; font-family: var(--display); font-size: 15px; cursor: pointer; text-align: center; margin-bottom: 10px; }
.pm-btn-primary { background: var(--purple); color: #fff; }
.pm-btn-primary:active { filter: brightness(0.9); }
.pm-btn-trial { background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple); }
.pm-btn-trial:active { filter: brightness(0.9); }
.pm-cancel { display: block; width: 100%; padding: 14px; background: none; border: none; color: var(--muted); font-size: 14px; font-weight: 700; cursor: pointer; }
```

**Step 2: Update the body section**

Wrap the page div with `x-data="taskBrowser()"`.

After each task's text div and before the `@if(!empty($task['id']))` block, add:

```blade
{{-- ANSWER ROW --}}
@if(!empty($task['answer']))
  <div class="answer-row">
    <span class="answer-label">Ответ:</span>
    @if($isPremium)
      <span class="answer-value">{{ $task['answer'] }}</span>
    @else
      <span class="answer-blur">{{ $task['answer'] }}</span>
      <span class="premium-cta" @click="showPremium = true">🔓 Premium</span>
    @endif
  </div>
@endif
```

Add the same premium modal template before closing `</div>` of `.page`.

**Step 3: Add the same `taskBrowser()` Alpine.js script**

Add `@push('scripts')` with the same `taskBrowser()` function as Task 6.

**Step 4: Commit**

```bash
git add resources/views/miniapp/part2.blade.php
git commit -m "feat: show blurred/premium answers in Part 2 task browser"
```

---

## Task 8: Premium Answer UI in new-tasks.blade.php

**Files:**
- Modify: `resources/views/miniapp/new-tasks.blade.php`

Same pattern as Tasks 6 & 7:
1. Add answer/premium CSS styles to `@push('styles')`
2. Wrap `.page` with `x-data="taskBrowser()"`
3. Add answer row after task text in BOTH rendering paths (grouped spoiler for topic 10, and flat list for others)
4. Add premium modal
5. Add `@push('scripts')` with `taskBrowser()` function

**Commit:**

```bash
git add resources/views/miniapp/new-tasks.blade.php
git commit -m "feat: show blurred/premium answers in new-tasks browser"
```

---

## Task 9: Profile Page

**Files:**
- Create: `resources/views/miniapp/profile.blade.php`
- Modify: `app/Http/Controllers/MiniAppController.php`

**Step 1: Add profile controller method**

Add to `MiniAppController`:

```php
/**
 * User profile — premium status, star balance, referrals, transactions.
 */
public function profile()
{
    $user = Auth::user();

    $transactions = StarTransaction::where('user_id', $user->id)
        ->orderByDesc('created_at')
        ->limit(50)
        ->get();

    $referralCount = User::where('referred_by_user_id', $user->id)->count();
    $pendingPayout = StarTransaction::where('user_id', $user->id)
        ->where('type', 'payout')
        ->where('status', 'pending')
        ->exists();

    return view('miniapp.profile', [
        'user' => $user,
        'isPremium' => $user->hasTgPremium(),
        'trialUsed' => (bool) $user->tg_trial_used,
        'transactions' => $transactions,
        'referralCount' => $referralCount,
        'pendingPayout' => $pendingPayout,
    ]);
}
```

**Step 2: Create profile view**

```blade
@extends('layouts.miniapp')
@section('title', 'Профиль — palomatika')

@push('styles')
  .profile-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 20px; text-align: center;
    opacity: 0; animation: fadeUp 0.3s ease 0.06s forwards;
  }
  .profile-name { font-family: var(--display); font-size: 20px; color: var(--text); margin-bottom: 4px; }
  .profile-role { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }

  .premium-badge {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 12px; padding: 6px 14px; border-radius: 10px;
    font-size: 12px; font-weight: 800;
  }
  .premium-badge.active { background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple); }
  .premium-badge.inactive { background: var(--surface2); border: 1px solid var(--border); color: var(--muted); }

  .stats-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards;
  }
  .stat-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 16px; text-align: center;
  }
  .stat-num { font-family: var(--display); font-size: 24px; color: var(--text); }
  .stat-label { font-size: 11px; font-weight: 700; color: var(--muted); margin-top: 4px; }

  .tx-list {
    display: flex; flex-direction: column; gap: 8px;
    opacity: 0; animation: fadeUp 0.3s ease 0.18s forwards;
  }
  .tx-item {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    display: flex; justify-content: space-between; align-items: center;
  }
  .tx-info { }
  .tx-type { font-size: 13px; font-weight: 700; color: var(--text); }
  .tx-date { font-size: 10px; color: var(--muted); margin-top: 2px; }
  .tx-amount { font-family: var(--display); font-size: 16px; }
  .tx-amount.positive { color: var(--green); }
  .tx-amount.negative { color: var(--red); }
  .tx-amount.pending { color: var(--yellow); }

  .ref-box {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 16px;
    opacity: 0; animation: fadeUp 0.3s ease 0.14s forwards;
  }
  .ref-link {
    font-size: 12px; color: var(--accent); word-break: break-all;
    background: var(--surface2); padding: 10px 12px; border-radius: 10px;
    margin-top: 8px;
  }

  .pm-overlay {
    position: fixed; inset: 0; z-index: 100;
    background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
    display: flex; align-items: flex-end; justify-content: center;
  }
  .pm-sheet {
    background: var(--bg); border-radius: 20px 20px 0 0;
    width: 100%; max-width: 420px; padding: 24px 20px 32px;
  }
  .pm-handle { width: 36px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 16px; }
  .pm-title { font-family: var(--display); font-size: 20px; color: var(--text); text-align: center; margin-bottom: 8px; }
  .pm-desc { font-size: 13px; color: var(--muted); text-align: center; line-height: 1.5; margin-bottom: 20px; }
  .pm-price { font-family: var(--display); font-size: 28px; color: var(--text); text-align: center; margin-bottom: 20px; }
  .pm-price small { font-size: 14px; color: var(--muted); }
  .pm-btn { display: block; width: 100%; padding: 16px; border: none; border-radius: 14px; font-family: var(--display); font-size: 15px; cursor: pointer; text-align: center; margin-bottom: 10px; }
  .pm-btn-primary { background: var(--purple); color: #fff; }
  .pm-btn-primary:active { filter: brightness(0.9); }
  .pm-btn-trial { background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple); }
  .pm-btn-trial:active { filter: brightness(0.9); }
  .pm-cancel { display: block; width: 100%; padding: 14px; background: none; border: none; color: var(--muted); font-size: 14px; font-weight: 700; cursor: pointer; }
@endpush

@section('body')
<div class="page" x-data="profilePage()">
  <div class="topbar">
    <a href="/tg/dashboard" class="back-btn">‹</a>
    <div class="topbar-title">Профиль</div>
  </div>

  {{-- PROFILE CARD --}}
  <div class="profile-card">
    <div class="profile-name">{{ $user->name }}</div>
    <div class="profile-role">{{ $user->role === 'teacher' ? 'Учитель' : 'Ученик' }}</div>

    @if($isPremium)
      <div class="premium-badge active">⭐ Premium до {{ $user->tg_premium_until->format('d.m.Y') }}</div>
    @else
      <div class="premium-badge inactive" style="cursor:pointer" @click="showPremium = true">Активировать Premium</div>
    @endif
  </div>

  {{-- STATS --}}
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-num">{{ $user->star_balance }}</div>
      <div class="stat-label">⭐ Баланс</div>
    </div>
    <div class="stat-card">
      <div class="stat-num">{{ $referralCount }}</div>
      <div class="stat-label">👥 Приглашено</div>
    </div>
  </div>

  {{-- PAYOUT --}}
  @if($user->star_balance > 0 && !$pendingPayout)
  <button class="btn btn-accent" style="opacity:0; animation: fadeUp 0.3s ease 0.12s forwards;"
          @click="requestPayout()" :disabled="payoutLoading"
          x-text="payoutLoading ? 'Отправка...' : 'Заказать выплату (' + {{ $user->star_balance }} + ' ⭐)'">
  </button>
  @endif
  @if($pendingPayout)
  <div class="note" style="opacity:0; animation: fadeUp 0.3s ease 0.12s forwards;">
    ⏳ Заявка на выплату обрабатывается. Выплата в течение 24 часов.
  </div>
  @endif

  {{-- REFERRAL LINK --}}
  <div class="ref-box">
    <div class="sec-label">Пригласи друга — получи 30% ⭐</div>
    <div class="ref-link" @click="copyRefLink()" style="cursor:pointer">
      <span x-text="copied ? '✅ Скопировано!' : refLink"></span>
    </div>
    <div style="margin-top:10px; display:flex; gap:8px;">
      <button class="btn btn-surface" style="flex:1; padding:12px; font-size:13px;" @click="copyRefLink()">📋 Копировать</button>
      <button class="btn btn-surface" style="flex:1; padding:12px; font-size:13px;" @click="shareRefLink()">📤 Поделиться</button>
    </div>
  </div>

  {{-- TRANSACTIONS --}}
  @if($transactions->count() > 0)
  <div class="sec-label" style="opacity:0; animation: fadeUp 0.3s ease 0.16s forwards;">История операций</div>
  <div class="tx-list">
    @foreach($transactions as $tx)
    <div class="tx-item">
      <div class="tx-info">
        <div class="tx-type">
          @if($tx->type === 'purchase') 💎 Покупка Premium
          @elseif($tx->type === 'referral_bonus') 🎁 Реферальный бонус
          @elseif($tx->type === 'payout') 💸 Выплата
          @endif
        </div>
        <div class="tx-date">{{ $tx->created_at->format('d.m.Y H:i') }}</div>
      </div>
      <div class="tx-amount {{ $tx->amount > 0 ? 'positive' : ($tx->status === 'pending' ? 'pending' : 'negative') }}">
        {{ $tx->amount > 0 ? '+' : '' }}{{ $tx->amount }} ⭐
        @if($tx->status === 'pending') <span style="font-size:10px;">⏳</span> @endif
      </div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- PREMIUM MODAL --}}
  <template x-if="showPremium">
    <div class="pm-overlay" @click.self="showPremium = false">
      <div class="pm-sheet">
        <div class="pm-handle"></div>
        <div class="pm-title">⭐ Premium</div>
        <div class="pm-desc">Открой ответы ко всем заданиям в базе.<br>Подписка на 30 дней.</div>
        <div class="pm-price">100 ⭐ <small>/ мес</small></div>
        <button class="pm-btn pm-btn-primary" @click="buyPremium()" :disabled="buying" x-text="buying ? 'Загрузка...' : 'Купить за 100 ⭐'"></button>
        @if(!$trialUsed)
        <button class="pm-btn pm-btn-trial" @click="activateTrial()" :disabled="buying" x-text="trialActivating ? 'Активация...' : '🎁 7 дней бесплатно'"></button>
        @endif
        <button class="pm-cancel" @click="showPremium = false">Отмена</button>
      </div>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
function profilePage() {
  const botUsername = '{{ config("services.telegram.bot_username", "palomatika_auth_bot") }}';
  const refLink = `https://t.me/${botUsername}?startapp=ref_{{ $user->id }}`;

  return {
    showPremium: false,
    buying: false,
    trialActivating: false,
    payoutLoading: false,
    copied: false,
    refLink,

    async buyPremium() {
      if (this.buying) return;
      this.buying = true;
      try {
        const res = await window.fetchPost('/tg/premium/buy');
        const data = await res.json();
        if (data.invoice_url) {
          const tg = window.Telegram?.WebApp;
          if (tg && tg.openInvoice) {
            tg.openInvoice(data.invoice_url, (status) => {
              if (status === 'paid') window.location.reload();
              this.buying = false;
            });
          } else {
            window.open(data.invoice_url, '_blank');
            this.buying = false;
          }
        } else {
          alert(data.error || 'Ошибка');
          this.buying = false;
        }
      } catch (e) {
        alert('Ошибка соединения');
        this.buying = false;
      }
    },

    async activateTrial() {
      if (this.trialActivating) return;
      this.trialActivating = true;
      try {
        const res = await window.fetchPost('/tg/premium/trial');
        const data = await res.json();
        if (data.ok) window.location.reload();
        else { alert(data.error || 'Ошибка'); this.trialActivating = false; }
      } catch (e) { alert('Ошибка соединения'); this.trialActivating = false; }
    },

    async requestPayout() {
      if (this.payoutLoading) return;
      if (!confirm('Заказать выплату? Средства будут переведены в течение 24 часов.')) return;
      this.payoutLoading = true;
      try {
        const res = await window.fetchPost('/tg/premium/payout');
        const data = await res.json();
        if (data.ok) window.location.reload();
        else { alert(data.error || 'Ошибка'); this.payoutLoading = false; }
      } catch (e) { alert('Ошибка соединения'); this.payoutLoading = false; }
    },

    copyRefLink() {
      navigator.clipboard.writeText(this.refLink).then(() => {
        this.copied = true;
        setTimeout(() => { this.copied = false; }, 2000);
      });
    },

    shareRefLink() {
      const tg = window.Telegram?.WebApp;
      const text = 'Готовься к ОГЭ по математике! Зарегистрируйся и получи 7 дней Premium бесплатно ⭐';
      const shareUrl = `https://t.me/share/url?url=${encodeURIComponent(this.refLink)}&text=${encodeURIComponent(text)}`;
      if (tg && tg.openTelegramLink) {
        tg.openTelegramLink(shareUrl);
      } else {
        window.open(shareUrl, '_blank');
      }
    },
  };
}
</script>
@endpush
```

**Step 3: Commit**

```bash
git add resources/views/miniapp/profile.blade.php app/Http/Controllers/MiniAppController.php
git commit -m "feat: add student profile page with premium, balance, referrals"
```

---

## Task 10: Dashboard — Add Profile Tile

**Files:**
- Modify: `resources/views/miniapp/dashboard.blade.php`

**Step 1: Replace the "Позвать друга" tile with "Профиль" tile**

In `dashboard.blade.php` (line ~272), change:

```blade
<a href="#" class="tile-sm" @click.prevent="handleInvite()">
  <div class="tile-sm-icon">👥</div>
  <div class="tile-sm-name">Позвать друга</div>
  <div class="tile-sm-desc">Пусть тоже готовится</div>
</a>
```

To:

```blade
<a href="/tg/profile" class="tile-sm">
  <div class="tile-sm-icon">👤</div>
  <div class="tile-sm-name">Профиль</div>
  <div class="tile-sm-desc">Premium · Баланс · Рефералы</div>
  @if(Auth::user()->hasTgPremium())
  <div class="tile-badge badge-purple tile-badge-top-right" style="font-size:8px;">⭐</div>
  @endif
</a>
```

Keep `handleInvite()` in JS — it's also used in the profile page share.

**Step 2: Commit**

```bash
git add resources/views/miniapp/dashboard.blade.php
git commit -m "feat: add profile tile to dashboard"
```

---

## Task 11: Run Migration on Production

**Step 1: Deploy and run migration**

After pushing code, run on production:
```bash
php artisan migrate
php artisan cache:clear
```

Or use MCP `run_artisan` tool with `migrate` and `cache:clear`.

---

## Summary of all files

| Action | File |
|--------|------|
| Create | `database/migrations/2026_03_15_100000_add_telegram_premium_fields.php` |
| Create | `app/Models/StarTransaction.php` |
| Create | `resources/views/miniapp/profile.blade.php` |
| Modify | `app/Models/User.php` |
| Modify | `app/Http/Controllers/MiniAppController.php` |
| Modify | `app/Http/Controllers/Auth/TelegramBotAuthController.php` |
| Modify | `resources/views/miniapp/tasks-part1.blade.php` |
| Modify | `resources/views/miniapp/part2.blade.php` |
| Modify | `resources/views/miniapp/new-tasks.blade.php` |
| Modify | `resources/views/miniapp/dashboard.blade.php` |
| Modify | `routes/web.php` |
