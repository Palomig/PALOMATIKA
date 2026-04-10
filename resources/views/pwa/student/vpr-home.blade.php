@extends('layouts.pwa')
@section('title', 'ВПР ' . $grade . ' класс — palomatika')

@section('body')
<div class="home-container" style="min-height:100dvh;padding:24px 20px;max-width:480px;margin:0 auto;">

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;">
    <div style="font-family:var(--display);font-size:15px;color:var(--accent);">palomatika</div>
    <div style="background:var(--accent-bg);border:1px solid var(--accent);color:var(--accent);
                font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;letter-spacing:.08em;">
      ВПР · {{ $grade ?? '?' }} КЛАСС
    </div>
  </div>

  <div style="text-align:center;padding:32px 0 40px;">
    <div style="font-size:11px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;
                color:var(--muted);margin-bottom:16px;">Математика</div>
    <h1 style="font-family:var(--display);font-size:clamp(28px,8vw,40px);line-height:1.1;margin-bottom:8px;">
      Подготовка к <em style="color:var(--accent);">ВПР</em>
    </h1>
    <p style="font-size:14px;color:var(--muted);font-weight:600;">{{ $grade ?? '?' }} класс · 18 заданий</p>
  </div>

  {{-- Незавершённые попытки --}}
  @foreach($activeList as $att)
  <a href="{{ route('pwa.student.vpr.test', $att['id']) }}"
     style="display:block;background:var(--surface);border:1.5px solid var(--accent);
            border-radius:16px;padding:16px;margin-bottom:12px;text-decoration:none;">
    <div style="font-size:13px;font-weight:800;color:var(--text);">{{ $att['title'] }}</div>
    <div style="font-size:12px;color:var(--muted);margin-top:4px;">
      Отвечено {{ $att['answeredCount'] }} из {{ $att['totalCount'] }} · Продолжить →
    </div>
  </a>
  @endforeach

  <form method="POST" action="{{ route('pwa.student.vpr.start') }}">
    @csrf
    <button type="submit"
            style="width:100%;background:var(--accent);color:#fff;border:none;border-radius:14px;
                   padding:18px;font-family:var(--body);font-size:16px;font-weight:800;cursor:pointer;">
      Начать вариант ВПР
    </button>
  </form>

  @if($grade === 8)
  <a href="{{ route('pwa.student.oge-dashboard') }}"
     style="display:block;text-align:center;margin-top:16px;font-size:13px;
            color:var(--accent);font-weight:700;padding:12px;
            background:var(--accent-bg);border:1px solid var(--accent-bd);border-radius:12px;">
    Переключиться на ОГЭ →
  </a>
  @endif

  {{-- История --}}
  <a href="{{ route('pwa.student.history') }}"
     style="display:block;text-align:center;margin-top:20px;font-size:13px;
            color:var(--muted);font-weight:600;">История попыток</a>
</div>
@endsection
