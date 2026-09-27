{{-- Вход в банк «Скиллы» из базы заданий. Скиллы сквозные, поэтому кнопка
     одна и та же в ОГЭ, ЕГЭ и ВПР любого класса; ученику её не видно.
     $variant: 'option' — строка листа «База заданий», 'row' — ссылка на
     странице самого банка. --}}
@php($skillsViewer = auth()->user())
@if($skillsViewer && ($skillsViewer->isTeacher() || $skillsViewer->isAdmin()))
  @if(($variant ?? 'option') === 'option')
    <a href="{{ route('pwa.student.skills') }}" class="fv-option">
      <div class="fv-opt-icon">🧩</div>
      <div>
        <div class="fv-opt-name">Скиллы</div>
        <div class="fv-opt-desc">Сквозные навыки · только для учителя</div>
      </div>
    </a>
  @else
    <a href="{{ route('pwa.student.skills') }}"
       style="display:flex;align-items:center;gap:10px;margin-top:10px;padding:10px 12px;
              border-radius:10px;text-decoration:none;
              background:var(--purple-bg);border:1px solid var(--purple-bd);color:var(--purple);">
      <span style="font-size:16px;line-height:1;">🧩</span>
      <span style="font-size:13px;font-weight:800;">Скиллы</span>
      <span style="font-size:11px;font-weight:700;opacity:.8;">сквозные навыки · только для учителя</span>
      <span style="margin-left:auto;font-size:16px;line-height:1;">›</span>
    </a>
  @endif
@endif
