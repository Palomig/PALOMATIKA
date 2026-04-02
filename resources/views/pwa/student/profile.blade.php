@extends('layouts.pwa')
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
  .premium-badge.inactive { background: var(--surface2); border: 1px solid var(--border); color: var(--muted); cursor: pointer; }

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

  .ref-box {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 16px;
    opacity: 0; animation: fadeUp 0.3s ease 0.14s forwards;
  }
  .ref-link {
    font-size: 12px; color: var(--accent); word-break: break-all;
    background: var(--surface2); padding: 10px 12px; border-radius: 10px;
    margin-top: 8px; cursor: pointer;
  }

  .tx-list {
    display: flex; flex-direction: column; gap: 8px;
    opacity: 0; animation: fadeUp 0.3s ease 0.18s forwards;
  }
  .tx-item {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    display: flex; justify-content: space-between; align-items: center;
  }
  .tx-type { font-size: 13px; font-weight: 700; color: var(--text); }
  .tx-date { font-size: 10px; color: var(--muted); margin-top: 2px; }
  .tx-amount { font-family: var(--display); font-size: 16px; }
  .tx-amount.positive { color: var(--green); }
  .tx-amount.negative { color: var(--red); }
  .tx-amount.pending { color: var(--yellow); }

  .pm-overlay { position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,.55); backdrop-filter: blur(4px); display: flex; align-items: flex-end; justify-content: center; }
  .pm-sheet { background: var(--bg); border-radius: 20px 20px 0 0; width: 100%; max-width: 420px; padding: 24px 20px 32px; }
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
    <a href="/dashboard" class="back-btn">&#8249;</a>
    <div class="topbar-title">Профиль</div>
  </div>

  {{-- Profile card --}}
  <div class="profile-card">
    <div class="profile-name">{{ $user->name }}</div>
    <div class="profile-role">{{ $user->role === 'teacher' ? 'Учитель' : 'Ученик' }}</div>

    @if($isPremium)
      <div class="premium-badge active">Premium до {{ $user->tg_premium_until->format('d.m.Y') }}</div>
    @else
      <div class="premium-badge inactive" @click="showPremium = true">Активировать Premium</div>
    @endif
  </div>

  {{-- Stats --}}
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-num">{{ $user->star_balance }} ⭐</div>
      <div class="stat-label">Баланс</div>
    </div>
    <div class="stat-card">
      <div class="stat-num">{{ $referralCount }}</div>
      <div class="stat-label">Приглашено</div>
    </div>
  </div>

  {{-- Payout --}}
  @if($user->star_balance > 0 && !$pendingPayout)
  <button class="btn btn-accent" style="opacity:0; animation: fadeUp 0.3s ease 0.12s forwards;"
          @click="requestPayout()" :disabled="payoutLoading"
          x-text="payoutLoading ? 'Отправка...' : 'Заказать выплату ({{ $user->star_balance }})'">
  </button>
  @endif
  @if($pendingPayout)
  <div class="note" style="opacity:0; animation: fadeUp 0.3s ease 0.12s forwards;">
    Заявка на выплату обрабатывается. Выплата в течение 24 часов.
  </div>
  @endif

  {{-- Referral link --}}
  <div class="ref-box">
    <div class="sec-label">Пригласи друга — получи 20%</div>
    <div class="ref-link" @click="copyRefLink()">
      <span x-text="copied ? 'Скопировано!' : refLink"></span>
    </div>
    <div style="margin-top:10px; display:flex; gap:8px;">
      <button class="btn btn-surface" style="flex:1; padding:12px; font-size:13px;" @click="copyRefLink()">Копировать</button>
      <button class="btn btn-surface" style="flex:1; padding:12px; font-size:13px;" @click="shareRefLink()">Поделиться</button>
    </div>
  </div>

  {{-- Transactions --}}
  @if($transactions->count() > 0)
  <div class="sec-label" style="opacity:0; animation: fadeUp 0.3s ease 0.16s forwards;">История операций</div>
  <div class="tx-list">
    @foreach($transactions as $tx)
    <div class="tx-item">
      <div>
        <div class="tx-type">
          @if($tx->type === 'purchase') Покупка Premium
          @elseif($tx->type === 'referral_bonus') Реферальный бонус
          @elseif($tx->type === 'payout') Выплата
          @endif
        </div>
        <div class="tx-date">{{ $tx->created_at->format('d.m.Y H:i') }}</div>
      </div>
      <div class="tx-amount {{ $tx->amount > 0 ? 'positive' : ($tx->status === 'pending' ? 'pending' : 'negative') }}">
        {{ $tx->amount > 0 ? '+' : '' }}{{ $tx->amount }}
        @if($tx->status === 'pending') <span style="font-size:10px;"></span> @endif
      </div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- Premium modal --}}
  <template x-if="showPremium">
    <div class="pm-overlay" @click.self="showPremium = false">
      <div class="pm-sheet">
        <div class="pm-handle"></div>
        <div class="pm-title">Premium</div>
        <div class="pm-desc">Открой ответы ко всем заданиям в базе.<br>Подписка на 30 дней.</div>
        <div class="pm-price">100 ⭐ <small>/ мес</small></div>
        <button class="pm-btn pm-btn-primary" @click="buyPremium()" :disabled="buying" x-text="buying ? 'Загрузка...' : 'Купить'"></button>
        @if(!$trialUsed)
        <button class="pm-btn pm-btn-trial" @click="activateTrial()" :disabled="buying" x-text="trialActivating ? 'Активация...' : '7 дней бесплатно'"></button>
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
  const miniAppShort = '{{ config("services.telegram.mini_app_short_name", "") }}';
  const refLink = miniAppShort
    ? `https://t.me/${botUsername}/${miniAppShort}?startapp=ref_{{ $user->id }}`
    : `https://t.me/${botUsername}?start=ref_{{ $user->id }}`;

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
        const res = await window.fetchPost('/premium/buy');
        const data = await res.json();
        if (data.invoice_url) {
          const tg = window.Telegram?.WebApp;
          if (tg && tg.openInvoice) {
            tg.openInvoice(data.invoice_url, (status) => {
              if (status === 'paid') window.location.reload();
              this.buying = false;
            });
          } else { window.open(data.invoice_url, '_blank'); this.buying = false; }
        } else { alert(data.error || 'Ошибка'); this.buying = false; }
      } catch (e) { alert('Ошибка соединения'); this.buying = false; }
    },

    async activateTrial() {
      if (this.trialActivating) return;
      this.trialActivating = true;
      try {
        const res = await window.fetchPost('/premium/trial');
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
        const res = await window.fetchPost('/premium/payout');
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
      const text = 'Готовься к ОГЭ по математике! Зарегистрируйся и получи 7 дней Premium бесплатно';
      const shareUrl = `https://t.me/share/url?url=${encodeURIComponent(this.refLink)}&text=${encodeURIComponent(text)}`;
      const tg = window.Telegram?.WebApp;
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
