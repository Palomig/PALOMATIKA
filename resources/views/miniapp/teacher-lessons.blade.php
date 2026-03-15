@extends('layouts.miniapp')
@section('title', 'Уроки сегодня — palomatika')

@push('styles')
  .lessons-stack { display:flex; flex-direction:column; gap:16px; }
  .status-legend { display:flex; gap:8px; flex-wrap:wrap; }
  .lesson-slot {
    padding:16px; border-radius:22px; background:var(--surface); border:1px solid var(--border);
    box-shadow:0 12px 34px rgba(0,0,0,.2);
  }
  .lesson-slot-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
  .lesson-slot-title { font-size:18px; font-weight:900; color:var(--text); }
  .lesson-slot-subtitle { margin-top:4px; font-size:12px; color:var(--muted); }
  .lesson-slot-students { display:flex; flex-direction:column; gap:10px; margin-top:14px; }
  .lesson-slot-student {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:14px; border-radius:18px; background:var(--surface2); border:1px solid var(--border);
  }
  .lesson-slot-student strong { display:block; font-size:14px; color:var(--text); }
  .lesson-slot-student span { display:block; margin-top:4px; font-size:12px; color:var(--muted); }
  .lesson-slot-actions { display:flex; gap:8px; flex-wrap:wrap; }
@endpush

@section('body')
<div class="page page--teacher">
  <div class="lessons-stack">
    <section class="mini-hero">
      <div class="hero-kicker">Уроки сегодня · {{ $todayLabel }}</div>
      <div class="hero-title">Слоты дня и ученики внутри</div>
      <div class="hero-subtitle">Каждый урок показывает статус `прошёл`, `идёт` или `будет`, а внутри можно перейти в профиль ученика или сразу выдать домашку.</div>
      <div class="status-legend" style="margin-top:14px;">
        <span class="status-tag red">прошёл</span>
        <span class="status-tag green">идёт</span>
        <span class="status-tag accent">будет</span>
      </div>
    </section>

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
                <span>{{ $student['student_full_name'] ?: $student['evrium_name'] }} · {{ $student['risk_label'] }}</span>
              </div>
              <div class="lesson-slot-actions">
                @if($student['student_id'])
                  <a href="/tg/teacher/students/{{ $student['student_id'] }}" class="ghost-link">Профиль</a>
                  <a href="/tg/teacher/homework" class="ghost-link">Дать ДЗ</a>
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
