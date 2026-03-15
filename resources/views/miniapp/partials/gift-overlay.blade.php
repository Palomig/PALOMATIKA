{{-- Gift box overlay --}}
@if($pendingGift ?? null)
<style>
  .gift-overlay {
    position: fixed; inset: 0; z-index: 200;
    background: rgba(0,0,0,.7); backdrop-filter: blur(8px);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 24px; padding-top: 20vh;
    animation: fadeIn 0.4s ease;
    overflow-y: auto;
  }

  /* GIFT BOX */
  .gift-box-wrap { position: relative; width: 160px; height: 170px; cursor: pointer; }

  .gift-box {
    position: absolute; bottom: 0; width: 160px; height: 120px;
    background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%);
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(124, 58, 237, 0.4);
  }
  .gift-box::after {
    content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%);
    width: 28px; height: 100%;
    background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
    border-radius: 4px;
  }

  .gift-lid {
    position: absolute; top: 0; left: -8px; width: 176px; height: 44px;
    background: linear-gradient(135deg, #c4b5fd 0%, #a78bfa 100%);
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(124, 58, 237, 0.3);
    transform-origin: center bottom;
  }
  .gift-lid::after {
    content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%);
    width: 32px; height: 100%;
    background: linear-gradient(180deg, #fcd34d 0%, #fbbf24 100%);
    border-radius: 4px;
  }

  .gift-bow {
    position: absolute; top: -22px; left: 50%; transform: translateX(-50%);
    width: 50px; height: 30px; z-index: 2;
  }
  .gift-bow::before, .gift-bow::after {
    content: ''; position: absolute; top: 8px;
    width: 24px; height: 20px;
    border: 4px solid #fbbf24;
    border-radius: 50% 50% 50% 50%;
    background: transparent;
  }
  .gift-bow::before { left: 0; transform: rotate(-20deg); }
  .gift-bow::after { right: 0; transform: rotate(20deg); }
  .gift-bow-center {
    position: absolute; top: 14px; left: 50%; transform: translateX(-50%);
    width: 14px; height: 14px;
    background: #fbbf24; border-radius: 50%;
    z-index: 3;
  }

  /* SHAKE */
  .gift-shake { animation: giftShake 0.5s ease-in-out infinite; }
  @keyframes giftShake {
    0%, 100% { transform: rotate(0deg); }
    15% { transform: rotate(5deg) translateX(4px); }
    30% { transform: rotate(-4deg) translateX(-4px); }
    45% { transform: rotate(3deg) translateX(2px); }
    60% { transform: rotate(-2deg) translateX(-2px); }
    75% { transform: rotate(1deg); }
  }

  /* OPEN ANIMATION */
  .gift-opening .gift-lid {
    animation: lidFly 0.6s ease forwards;
  }
  @keyframes lidFly {
    0% { transform: rotate(0) translateY(0); opacity: 1; }
    60% { transform: rotate(-35deg) translateY(-80px) translateX(-20px); opacity: 1; }
    100% { transform: rotate(-50deg) translateY(-160px) translateX(-40px); opacity: 0; }
  }
  .gift-opening .gift-box {
    animation: boxBurst 0.7s ease 0.3s forwards;
  }
  @keyframes boxBurst {
    0% { transform: scale(1); opacity: 1; }
    40% { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(0); opacity: 0; }
  }
  .gift-opening .gift-bow,
  .gift-opening .gift-bow-center {
    animation: lidFly 0.6s ease forwards;
  }

  /* PARTICLES */
  .gift-particles {
    position: absolute; inset: 0; pointer-events: none; overflow: hidden;
  }
  .particle {
    position: absolute; width: 10px; height: 10px;
    border-radius: 2px; opacity: 0;
  }
  .gift-opening .particle { animation: particleBurst 1s ease forwards; }

  @keyframes particleBurst {
    0% { opacity: 1; transform: translate(0, 0) scale(1) rotate(0deg); }
    100% { opacity: 0; transform: var(--p-end) scale(0.3) rotate(720deg); }
  }

  /* STARS (after open) */
  .gift-stars {
    position: absolute; inset: 0; pointer-events: none; overflow: hidden;
  }
  .star-particle {
    position: absolute; font-size: 20px; opacity: 0;
  }
  .gift-opening .star-particle { animation: starFloat 1.2s ease forwards; }
  @keyframes starFloat {
    0% { opacity: 0; transform: translateY(0) scale(0.5); }
    30% { opacity: 1; transform: translateY(-20px) scale(1); }
    100% { opacity: 0; transform: var(--s-end) scale(0.4); }
  }

  /* RESULT TEXT */
  .gift-result {
    text-align: center; opacity: 0; transform: translateY(20px);
    transition: all 0.5s ease;
    pointer-events: none;
  }
  .gift-result.visible {
    opacity: 1; transform: translateY(0);
    pointer-events: auto;
  }
  .gift-result-emoji { font-size: 48px; margin-bottom: 12px; }
  .gift-result-title {
    font-family: var(--display); font-size: 24px; color: #fff;
    margin-bottom: 8px;
  }
  .gift-result-desc {
    font-size: 14px; color: rgba(255,255,255,.7); line-height: 1.6;
    margin-bottom: 24px; max-width: 300px;
  }
  .gift-result-btn {
    display: inline-block; padding: 14px 48px;
    background: linear-gradient(135deg, #a78bfa, #7c3aed);
    color: #fff; border: none; border-radius: 14px;
    font-family: var(--display); font-size: 15px;
    cursor: pointer; margin-bottom: 16px;
  }
  .gift-result-btn:active { filter: brightness(0.9); }

  .gift-open-btn {
    margin-top: 20px; padding: 14px 40px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #7c2d12; border: none; border-radius: 14px;
    font-family: var(--display); font-size: 15px;
    cursor: pointer;
    animation: btnPulse 1.5s ease infinite;
  }
  .gift-open-btn:active { filter: brightness(0.9); }
  @keyframes btnPulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(251,191,36,.4); }
    50% { transform: scale(1.04); box-shadow: 0 0 20px 4px rgba(251,191,36,.3); }
  }

  /* REFERRAL CARD */
  .gift-ref-card {
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    border-radius: 16px; padding: 16px 20px; max-width: 320px;
    text-align: center;
  }
  .gift-ref-title {
    font-family: var(--display); font-size: 14px; color: #fbbf24;
    margin-bottom: 6px;
  }
  .gift-ref-text { font-size: 12px; color: rgba(255,255,255,.6); line-height: 1.5; }
</style>

<div class="gift-overlay" x-data="giftBox()" x-show="visible" x-cloak>
  {{-- Gift box --}}
  <div class="gift-box-wrap" :class="{ 'gift-shake': !opened, 'gift-opening': opened }"
       @click="!opened && openGift()">
    <div class="gift-bow"></div>
    <div class="gift-bow-center"></div>
    <div class="gift-lid"></div>
    <div class="gift-box"></div>

    {{-- Particles --}}
    <div class="gift-particles">
      @for($i = 0; $i < 20; $i++)
        @php
          $colors = ['#a78bfa','#fbbf24','#34d07e','#4f8ef7','#f06060','#c4b5fd','#fcd34d'];
          $color = $colors[$i % count($colors)];
          $angle = ($i / 20) * 360;
          $dist = rand(80, 200);
          $x = round(cos(deg2rad($angle)) * $dist);
          $y = round(sin(deg2rad($angle)) * $dist);
          $delay = $i * 0.03 + 0.3;
          $size = rand(6, 14);
        @endphp
        <div class="particle" style="
          left: 50%; top: 50%;
          width: {{ $size }}px; height: {{ $size }}px;
          background: {{ $color }};
          animation-delay: {{ $delay }}s;
          --p-end: translate({{ $x }}px, {{ $y }}px);
          border-radius: {{ $i % 3 === 0 ? '50%' : '2px' }};
        "></div>
      @endfor
    </div>

    {{-- Stars --}}
    <div class="gift-stars">
      @for($i = 0; $i < 8; $i++)
        @php
          $angle = ($i / 8) * 360;
          $dist = rand(100, 180);
          $x = round(cos(deg2rad($angle)) * $dist);
          $y = round(sin(deg2rad($angle)) * $dist) - 60;
          $delay = $i * 0.08 + 0.4;
        @endphp
        <div class="star-particle" style="
          left: 50%; top: 40%;
          animation-delay: {{ $delay }}s;
          --s-end: translate({{ $x }}px, {{ $y }}px);
        ">⭐</div>
      @endfor
    </div>
  </div>

  {{-- Open button (before opening) --}}
  <button class="gift-open-btn" x-show="!opened" @click="openGift()">Открыть</button>

  {{-- Result (after opening) --}}
  <div class="gift-result" :class="{ 'visible': showResult }">
    <div class="gift-result-emoji">🎉</div>
    <div class="gift-result-title">{{ $pendingGift->payload['message'] ?? 'Вы получили Premium!' }}</div>
    <div class="gift-result-desc">
      Теперь вам доступны ответы ко всем заданиям в базе
    </div>
    <button class="gift-result-btn" @click="dismiss()">Отлично!</button>

    <div class="gift-ref-card">
      <div class="gift-ref-title">Пригласи одноклассников!</div>
      <div class="gift-ref-text">
        Приглашай друзей и получай 20% от звёзд, которые они тратят в приложении.
        Ссылка для приглашения — в профиле.
      </div>
    </div>
  </div>
</div>

<script>
function giftBox() {
  return {
    visible: true,
    opened: false,
    showResult: false,
    openGift() {
      this.opened = true;
      setTimeout(() => { this.showResult = true; }, 900);
    },
    dismiss() {
      this.visible = false;
      // Mark gift as shown
      window.fetchPost('/tg/gift/seen', { gift_id: {{ $pendingGift->id }} });
    }
  };
}
</script>
@endif
