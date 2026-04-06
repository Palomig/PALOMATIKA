@extends('layouts.pwa')
@section('title', 'Репетитор — palomatika')

@push('styles')
  /* AUDIENCE TOGGLE */
  .audience-toggle {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 4px; display: flex; gap: 4px;
    opacity: 0; animation: fadeUp 0.3s ease 0.06s forwards;
  }
  .toggle-btn {
    flex: 1; padding: 10px; border: none; border-radius: 10px;
    font-family: var(--body); font-size: 13px; font-weight: 700;
    color: var(--muted); background: transparent;
    cursor: pointer; transition: all 0.2s; text-align: center;
  }
  .toggle-btn.active { background: var(--accent); color: #fff; }

  /* HERO CARD */
  .tutor-hero {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 20px;
    opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards;
  }
  .tutor-avatar { font-size: 48px; margin-bottom: 12px; }
  .tutor-name { font-family: var(--display); font-size: 20px; color: var(--text); margin-bottom: 4px; }
  .tutor-role { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 14px; }
  .tutor-text { font-size: 13px; font-weight: 600; color: var(--muted); line-height: 1.6; }
  .tutor-text strong { color: var(--text); }

  /* INFO ROWS */
  .info-rows {
    display: flex; flex-direction: column; gap: 8px;
    opacity: 0; animation: fadeUp 0.3s ease 0.14s forwards;
  }
  .info-row {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 14px 16px;
    display: flex; align-items: center; gap: 12px;
  }
  .info-icon { font-size: 20px; flex-shrink: 0; }
  .info-label { font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; }
  .info-value { font-size: 14px; font-weight: 700; color: var(--text); margin-top: 1px; }

  /* WHAT YOU GET */
  .wyg-section { opacity: 0; animation: fadeUp 0.3s ease 0.18s forwards; }
  .wyg-list { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
  .wyg-item {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    display: flex; align-items: center; gap: 10px;
  }
  .wyg-check { color: var(--green); font-size: 16px; flex-shrink: 0; }
  .wyg-text { font-size: 13px; font-weight: 700; color: var(--text); }

  /* PARENT VIEW */
  .parent-insight {
    background: var(--surface); border: 1px solid var(--border);
    border-left: 3px solid var(--yellow);
    border-radius: var(--r); padding: 18px;
    opacity: 0; animation: fadeUp 0.3s ease 0.1s forwards;
  }
  .parent-title { font-family: var(--display); font-size: 18px; color: var(--text); margin-bottom: 10px; }
  .parent-text { font-size: 13px; font-weight: 600; color: var(--muted); line-height: 1.6; }
  .parent-text strong { color: var(--text); }

  .parent-facts { margin-top: 14px; display: flex; flex-direction: column; gap: 6px; }
  .parent-fact {
    display: flex; align-items: flex-start; gap: 8px;
    font-size: 12px; font-weight: 600; color: var(--muted); line-height: 1.5;
  }
  .parent-fact-icon { flex-shrink: 0; margin-top: 2px; }

  /* CTA BUTTONS */
  .cta-group {
    display: flex; flex-direction: column; gap: 10px;
    opacity: 0; animation: fadeUp 0.3s ease 0.22s forwards;
  }
  .cta-btn {
    width: 100%; padding: 16px; border: none; border-radius: 14px;
    font-family: var(--body); font-size: 15px; font-weight: 800;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: transform 0.1s; text-decoration: none;
  }
  .cta-btn:active { transform: scale(0.97); }
  .cta-tg { background: #2AABEE; color: #fff; }
  .cta-phone { background: var(--green); color: #fff; }
  .cta-map { background: var(--surface); border: 1px solid var(--border); color: var(--text); }
@endpush

@section('body')
<div class="page" x-data="tutorPage()">

  <div class="topbar">
    <a href="{{ route('miniapp.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Репетитор</div>
  </div>

  <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="tutor-hero">
        <div class="tutor-avatar">👨‍🏫</div>
        <div class="tutor-name">Станислав Олегович · Руслан Романович</div>
        <div class="tutor-role">Репетиторы по математике · г. Чехов</div>
        <div class="tutor-text">
          Готовим к ОГЭ уже <strong>более 5 лет</strong>.
          Дадим чёткий план, разберём типовые ловушки и доведём до уверенного результата.
          @if(isset($weakTopics) && count($weakTopics) > 0)
          <br><br>По твоим результатам, стоит подтянуть:
          <strong>{{ collect($weakTopics)->pluck('name')->take(3)->join(', ') }}</strong>
          @endif
        </div>
      </div>

      <div class="info-rows">
        <div class="info-row">
          <div class="info-icon">📍</div>
          <div>
            <div class="info-label">Адрес</div>
            <div class="info-value">г. Чехов, ул. Московская, 87/1</div>
          </div>
        </div>
        <div class="info-row">
          <div class="info-icon">⏰</div>
          <div>
            <div class="info-label">Формат</div>
            <div class="info-value">Очно · индивидуально или мини-группы</div>
          </div>
        </div>
        <div class="info-row">
          <div class="info-icon">🎁</div>
          <div>
            <div class="info-label">Первый урок</div>
            <div class="info-value" style="color:var(--green);">Бесплатно</div>
          </div>
        </div>
      </div>

      <div class="wyg-section">
        <div class="sec-label">Что получишь</div>
        <div class="wyg-list">
          <div class="wyg-item"><span class="wyg-check">✓</span><span class="wyg-text">Разбор каждого типа заданий</span></div>
          <div class="wyg-item"><span class="wyg-check">✓</span><span class="wyg-text">Индивидуальный план подготовки</span></div>
          <div class="wyg-item"><span class="wyg-check">✓</span><span class="wyg-text">Работа над слабыми темами</span></div>
          <div class="wyg-item"><span class="wyg-check">✓</span><span class="wyg-text">Стратегии решения на экзамене</span></div>
        </div>
      </div>

      <div class="cta-group">
        <a href="https://t.me/Palomig" class="cta-btn cta-tg" @click.prevent="openTg()">✈️ Написать в Telegram</a>
        <a href="tel:+79103017110" class="cta-btn cta-phone">📞 Позвонить</a>
        <a href="https://yandex.ru/maps/?text=г.Чехов,+ул.+Московская,+87/1" class="cta-btn cta-map" target="_blank">📍 На карте</a>
      </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function tutorPage() {
  return {
    openTg() {
      const tg = window.Telegram?.WebApp;
      if (tg) { tg.openTelegramLink('https://t.me/Palomig'); }
      else { window.open('https://t.me/Palomig', '_blank'); }
    },
  };
}
</script>
@endpush
