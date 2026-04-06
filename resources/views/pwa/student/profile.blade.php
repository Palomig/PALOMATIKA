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

  /* Share sheet */
  .share-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,.55); backdrop-filter: blur(4px); display: flex; align-items: flex-end; justify-content: center; }
  .share-sheet { background: var(--bg); border-radius: 20px 20px 0 0; width: 100%; max-width: 480px; padding: 16px 20px 32px; }
  .share-handle { width: 36px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 16px; }
  .share-title { font-size: 13px; font-weight: 700; color: var(--muted); text-align: center; margin-bottom: 20px; letter-spacing: .04em; text-transform: uppercase; }
  .share-link-row { display: flex; align-items: center; gap: 10px; background: var(--surface2); border-radius: 12px; padding: 10px 14px; margin-bottom: 20px; }
  .share-link-text { flex: 1; font-size: 12px; color: var(--accent); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .share-copy-btn { flex-shrink: 0; background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: 7px 14px; font-size: 12px; font-weight: 700; cursor: pointer; transition: opacity .15s; }
  .share-copy-btn:active { opacity: .8; }
  .share-apps { display: flex; justify-content: center; gap: 24px; }
  .share-app { display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; }
  .share-app-icon { width: 52px; height: 52px; border-radius: 16px; display: flex; align-items: center; justify-content: center; }
  .share-app-label { font-size: 11px; color: var(--muted); font-weight: 600; }
  .share-cancel { display: block; width: 100%; padding: 14px; background: none; border: none; color: var(--muted); font-size: 14px; font-weight: 700; cursor: pointer; margin-top: 12px; }

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
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">&#8249;</a>
    <div class="topbar-title">Профиль</div>
  </div>

  {{-- Profile card --}}
  <div class="profile-card">
    <div class="profile-name">{{ $user->name }}</div>
    <div class="profile-role">{{ $user->role === 'teacher' ? 'Учитель' : 'Ученик' }}</div>

    @if($isPremium)
      <div class="premium-badge active">Premium до {{ $user->tg_premium_until?->format('d.m.Y') ?? '∞' }}</div>
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
    <div style="margin-top:10px;">
      <button class="btn btn-surface" style="width:100%; padding:13px; font-size:14px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:8px;" @click="showShare = true">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
        Поделиться ссылкой
      </button>
    </div>
  </div>

  {{-- Share sheet --}}
  <template x-if="showShare">
    <div class="share-overlay" @click.self="showShare = false">
      <div class="share-sheet" @click.stop>
        <div class="share-handle"></div>
        <div class="share-title">Поделиться</div>

        <div class="share-link-row">
          <span class="share-link-text" x-text="refLink"></span>
          <button class="share-copy-btn" @click="copyRefLink()" x-text="copied ? '✓ Скопировано' : 'Копировать'"></button>
        </div>

        <div class="share-apps">
          <div class="share-app" @click="shareTo('telegram')">
            <div class="share-app-icon" style="background:#229ED9;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-2.04 9.613c-.154.683-.554.85-1.123.528l-3.1-2.285-1.496 1.44c-.165.165-.305.305-.625.305l.223-3.168 5.77-5.213c.25-.222-.055-.346-.39-.124L7.19 14.447l-3.04-.952c-.662-.207-.674-.662.138-.979l11.87-4.576c.552-.2 1.035.134.404.308z"/></svg>
            </div>
            <span class="share-app-label">Telegram</span>
          </div>

          <div class="share-app" @click="shareTo('whatsapp')">
            <div class="share-app-icon" style="background:#25D366;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </div>
            <span class="share-app-label">WhatsApp</span>
          </div>

          <div class="share-app" @click="shareTo('vk')">
            <div class="share-app-icon" style="background:#0077FF;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-1-1.49-1.135-1.744-1.135-.356 0-.458.102-.458.593v1.575c0 .424-.135.678-1.253.678-1.846 0-3.896-1.118-5.335-3.202C4.624 10.857 4.03 8.57 4.03 8.096c0-.254.102-.491.593-.491h1.744c.44 0 .61.203.78.677.863 2.49 2.303 4.675 2.896 4.675.22 0 .322-.102.322-.66V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.204.17-.407.44-.407h2.743c.373 0 .508.203.508.643v3.473c0 .372.17.508.271.508.22 0 .407-.136.813-.542 1.254-1.406 2.151-3.574 2.151-3.574.119-.254.322-.491.763-.491h1.744c.525 0 .644.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.254.44 0 .78.186.254.796.779 1.203 1.253.745.847 1.32 1.558 1.473 2.05.17.49-.085.744-.576.744z"/></svg>
            </div>
            <span class="share-app-label">ВКонтакте</span>
          </div>

          <div class="share-app" @click="shareTo('native')">
            <div class="share-app-icon" style="background:var(--surface2); border:1px solid var(--border);">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </div>
            <span class="share-app-label">Ещё</span>
          </div>
        </div>

        <button class="share-cancel" @click="showShare = false">Закрыть</button>
      </div>
    </div>
  </template>

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
    showShare: false,
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

    shareTo(platform) {
      const url = encodeURIComponent(this.refLink);
      const text = encodeURIComponent('Готовлюсь к ОГЭ по математике на Palomatika — присоединяйся, получишь 7 дней Premium бесплатно!');
      const links = {
        telegram: `https://t.me/share/url?url=${url}&text=${text}`,
        whatsapp: `https://wa.me/?text=${text}%20${url}`,
        vk:       `https://vk.com/share.php?url=${url}&title=${text}`,
      };
      if (platform === 'native' && navigator.share) {
        navigator.share({ title: 'Palomatika', text: decodeURIComponent(text), url: this.refLink });
        return;
      }
      if (platform === 'native') { this.copyRefLink(); return; }
      const tg = window.Telegram?.WebApp;
      if (platform === 'telegram' && tg?.openTelegramLink) {
        tg.openTelegramLink(links.telegram);
      } else {
        window.open(links[platform] || links.telegram, '_blank');
      }
      this.showShare = false;
    },
  };
}
</script>
@endpush
