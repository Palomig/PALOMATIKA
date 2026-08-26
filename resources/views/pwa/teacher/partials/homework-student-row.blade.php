{{-- Одна строка ученика в раскрытой плашке домашки (вкладка «Статистика»). --}}
@php
  $subText = match ($s['state']) {
      'completed' => 'сдал всё',
      'partial' => 'сдал ' . $s['submitted'] . ($s['total'] ? ' из ' . $s['total'] : '') . ' — не доделал',
      'opened' => 'открыл, но ничего не сдал',
      default => $s['tracks_open'] ? 'не открывал' : 'не сдал',
  };
  $tag = $s['can_open'] ? 'a' : 'div';
@endphp
<{{ $tag }} class="hw-stu"
   @if($s['can_open']) href="{{ route('pwa.teacher.homework.submissions', $s['assignment']) }}" @endif>
  <span class="hw-stu-dot dot-{{ $s['state'] }}"></span>
  <div class="hw-stu-body">
    <div class="hw-stu-name">
      {{ $s['name'] }}
      @if($s['grade'])<span class="check-grade">{{ $s['grade'] }} класс</span>@endif
      @if($s['is_debt'])<span class="hw-status-badge badge-debt">долг</span>@endif
      @if($s['reviewed'])<span class="hw-status-badge badge-reviewed">проверено</span>@endif
    </div>
    <div class="hw-stu-sub">{{ $subText }}@if($s['at']) · {{ $s['at'] }}@endif</div>
  </div>
  @if($s['can_open'])<span class="hw-stu-go">›</span>@endif
</{{ $tag }}>
