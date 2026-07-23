@extends('layouts.pwa')
@section('title', 'Результаты — palomatika')

@include('pwa.student.entrance10._assets')

@push('styles')
  .rs-hero { text-align:center; background:linear-gradient(180deg, rgba(79,142,247,.10), transparent), var(--surface);
    border:1.5px solid var(--accent-bd); border-radius:20px; padding:22px 18px; margin-bottom:16px; }
  .rs-score { font-family:var(--display); font-size:38px; line-height:1; }
  .rs-score small { font-size:20px; color:var(--muted); }
  .rs-sub { font-size:12.5px; font-weight:600; color:var(--muted); margin-top:6px; }
  .rs-group { background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:14px 16px; margin-bottom:12px; }
  .rs-group-head { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
  .rs-num { display:inline-flex; align-items:center; justify-content:center; min-width:38px; height:32px; padding:0 8px;
    border-radius:10px; background:var(--accent-bg); color:var(--accent); font-family:var(--display); font-weight:800; }
  .rs-group-title { font-family:var(--display); font-size:14.5px; }
  .rs-part { padding:11px 0; border-top:1px dashed var(--border); }
  .rs-part:first-of-type { border-top:none; }
  .rs-part-head { display:flex; align-items:center; gap:8px; margin-bottom:5px; }
  .rs-badge { font-weight:800; font-size:12.5px; border-radius:999px; padding:2px 9px; }
  .rs-badge.ok { background:rgba(22,163,74,.12); color:#16a34a; }
  .rs-badge.bad { background:rgba(220,38,38,.10); color:#dc2626; }
  .rs-badge.man { background:var(--accent-bg); color:var(--muted); }
  .rs-lab { font-weight:800; color:var(--accent); }
  .rs-text { font-size:13.5px; line-height:1.5; margin-bottom:6px; }
  .rs-ans { font-size:13px; line-height:1.6; }
  .rs-ans .k { color:var(--muted); font-weight:700; }
  .rs-ans .mine-bad { color:#dc2626; }
  .rs-sol { margin-top:6px; font-size:12.5px; color:var(--text); opacity:.9; }
  .rs-sol b { display:block; font-size:10.5px; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); margin-bottom:2px; }
  .rs-actions { display:flex; gap:10px; margin:6px 0 4px; }
  .rs-btn { flex:1; text-align:center; text-decoration:none; border-radius:14px; padding:13px; font-weight:800; font-size:14px; }
  .rs-btn-primary { background:var(--accent); color:#fff; }
  .rs-btn-ghost { background:var(--surface); border:1.5px solid var(--border); color:var(--text); }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.practice.entrance10.index') }}" class="back-btn">‹</a>
    <div class="topbar-title" style="font-size:15px;">Результаты</div>
  </div>

  <div class="rs-hero">
    <div class="rs-score">{{ $earned }}<small> / {{ $max }}</small></div>
    <div class="rs-sub">
      {{ $title }}
      @if(!is_null($time)) · {{ intdiv($time, 60) }} мин {{ $time % 60 }} с @endif
    </div>
  </div>

  <div class="rs-actions">
    <a href="{{ route('pwa.student.practice.entrance10.index') }}" class="rs-btn rs-btn-primary">Ещё вариант</a>
    <a href="{{ route('pwa.student.history') }}" class="rs-btn rs-btn-ghost">История</a>
  </div>

  @foreach($groups as $g)
    <div class="rs-group">
      <div class="rs-group-head">
        <span class="rs-num">№{{ $g['number'] }}</span>
        <span class="rs-group-title">{{ $g['title'] }}</span>
      </div>
      @foreach($g['parts'] as $p)
        <div class="rs-part">
          <div class="rs-part-head">
            <span class="rs-lab">{{ $p['display_label'] }})</span>
            @if($p['manual'])
              <span class="rs-badge man">проверка вручную</span>
            @elseif($p['is_correct'])
              <span class="rs-badge ok">✓ верно</span>
            @else
              <span class="rs-badge bad">✗ ошибка</span>
            @endif
            <span class="e10-points" style="margin-left:auto;">{{ $p['points'] }} б.</span>
          </div>
          <div class="rs-text">{!! $p['text'] !!}</div>
          @unless($p['manual'])
            <div class="rs-ans">
              <span class="k">Твой ответ:</span>
              <span class="{{ $p['is_correct'] ? '' : 'mine-bad' }}">{{ $p['user_answer'] !== '' ? $p['user_answer'] : '—' }}</span>
              @unless($p['is_correct'])
                <br><span class="k">Верный ответ:</span> {!! $p['answer_display'] !!}
              @endunless
            </div>
            @unless($p['is_correct'])
              @if(!empty($p['solution']))
                <div class="rs-sol"><b>Решение</b>{!! $p['solution'] !!}</div>
              @endif
            @endunless
          @endunless
        </div>
      @endforeach
    </div>
  @endforeach
</div>
@endsection
