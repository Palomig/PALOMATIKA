@extends('layouts.pwa')
@section('title', 'Уроки сегодня — palomatika')

@push('styles')
  .lessons-stack { display: flex; flex-direction: column; gap: 12px; }
  .legend { display:flex; gap:8px; flex-wrap:wrap; }
  .lesson-slot { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 12px; }
  .lesson-slot-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
  .lesson-slot-title { font-size: 16px; font-weight: 800; color: var(--text); }
  .lesson-slot-subtitle { margin-top: 4px; font-size: 12px; color: var(--muted); }
  .lesson-slot-students { display:flex; flex-direction:column; gap:8px; margin-top:12px; }
  .lesson-slot-student {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:10px 12px; border-radius:12px; background: var(--surface2); border:1px solid var(--border);
  }
  .lesson-slot-student strong { display:block; font-size:14px; color:var(--text); }
  .lesson-slot-student span { display:block; margin-top:4px; font-size:11px; color:var(--muted); }
  .lesson-slot-actions { display:flex; gap:8px; flex-wrap:wrap; }
  .status-tag {
    display:inline-flex; align-items:center; justify-content:center;
    min-height:24px; padding:0 10px; border-radius:999px; font-size:11px; font-weight:800;
  }
  .status-tag.red { color: var(--red); background: var(--red-bg); border: 1px solid var(--red-bd); }
  .status-tag.green { color: var(--green); background: var(--green-bg); border: 1px solid var(--green-bd); }
  .status-tag.accent { color: var(--accent); background: var(--accent-bg); border: 1px solid var(--accent-bd); }
@endpush

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Уроки сегодня</div>
  </div>

  <div class="note">
    Слоты на {{ $todayLabel }}. Статусы:
    <div class="legend" style="margin-top:8px;">
      <span class="status-tag red">прошёл</span>
      <span class="status-tag green">идёт</span>
      <span class="status-tag accent">будет</span>
    </div>
  </div>

  <div class="lessons-stack">
    @forelse($todayLessons as $lesson)
      <section class="lesson-slot">
        <div class="lesson-slot-head">
          <div>
            <div class="lesson-slot-title">{{ $lesson['time_start'] }}{{ $lesson['time_end'] ? ' - ' . $lesson['time_end'] : '' }}</div>
            <div class="lesson-slot-subtitle">{{ count($lesson['students']) }} ученик{{ count($lesson['students']) === 1 ? '' : (count($lesson['students']) >= 2 && count($lesson['students']) <= 4 ? 'а' : 'ов') }} в слоте</div>
          </div>
          <span class="status-tag {{ $lesson['status_key'] === 'current' ? 'green' : ($lesson['status_key'] === 'past' ? 'red' : 'accent') }}">{{ $lesson['status_label'] }}</span>
        </div>

        <div class="lesson-slot-students">
          @foreach($lesson['students'] as $student)
            <article class="lesson-slot-student">
              <div>
                <strong>{{ $student['student_name'] }}</strong>
                <span>{{ $student['student_full_name'] ?: $student['evrium_name'] }}</span>
              </div>
              <div class="lesson-slot-actions">
                @if($student['student_id'])
                  <a href="/students/{{ $student['student_id'] }}" class="btn btn-surface">Профиль</a>
                  <a href="/homework" class="btn btn-surface">Дать ДЗ</a>
                @else
                  <span class="status-tag red">Не привязан</span>
                @endif
              </div>
            </article>
          @endforeach
        </div>
      </section>
    @empty
      <div class="note">На сегодня слотов пока нет. Как только расписание Evrium вернёт уроки, здесь появятся временные блоки со статусами и списком учеников.</div>
    @endforelse
  </div>
</div>
@endsection
