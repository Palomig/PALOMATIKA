{{-- «Позови друга» — узкая строка под плиткой УРОК. Данные — view composer
     в AppServiceProvider: 8–11 класс (деньги) и 6–7 (скидка), прикреплённые к учителю. --}}
@if(!empty($friendStrip))
<a href="{{ route('pwa.student.friends') }}" class="fs-strip {{ $friendStrip['tone'] }}">
  <span class="fs-ico">{{ $friendStrip['icon'] }}</span>
  <span class="fs-mid">
    <span class="fs-t">{{ $friendStrip['title'] }}</span>
    @if($friendStrip['sub'] !== '')<span class="fs-s">{{ $friendStrip['sub'] }}</span>@endif
  </span>
  <span class="fs-more">Подробнее →</span>
</a>

@once
<style>
  .fs-strip { display: flex; align-items: center; gap: 12px; padding: 13px 14px; border-radius: 14px;
    background: var(--purple-bg); border: 1px solid var(--purple-bd); text-decoration: none; }
  .fs-ico { width: 34px; height: 34px; flex: 0 0 auto; border-radius: 10px; background: var(--purple-bg);
    display: flex; align-items: center; justify-content: center; font-size: 17px; }
  .fs-mid { flex: 1; min-width: 0; display: flex; flex-direction: column; }
  .fs-t { font-size: 13.5px; font-weight: 800; color: var(--text); line-height: 1.3; }
  .fs-s { font-size: 11.5px; color: var(--muted); font-weight: 600; margin-top: 2px; }
  .fs-more { font-size: 11px; font-weight: 800; color: var(--purple); flex: 0 0 auto; white-space: nowrap; }
  .fs-strip.warm { background: var(--green-bg); border-color: var(--green-bd); }
  .fs-strip.warm .fs-ico { background: var(--green-bg); }
  .fs-strip.warm .fs-more { color: var(--green); }
  .fs-strip.gold { background: var(--yellow-bg); border-color: var(--yellow-bd); }
  .fs-strip.gold .fs-ico { background: var(--yellow-bg); }
  .fs-strip.gold .fs-more { color: var(--yellow); }
</style>
@endonce
@endif
