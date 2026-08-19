{{-- Содержимое подробного разбора: шапка серии и сам текст с чертежами.
     Отдаётся и как отдельная страница, и фрагментом во всплывающее окно. --}}
<span class="sol-badge">📖 материал для учителя</span>
<div class="sol-title">{{ $title }}</div>
@if($subtitle)
  <div class="sol-subtitle">{{ $subtitle }}</div>
@endif

@if($instruction !== '')
  <div class="sol-instruction">{{ $instruction }}</div>
@endif

<div class="sol-body">
  {!! $solution !!}
</div>
