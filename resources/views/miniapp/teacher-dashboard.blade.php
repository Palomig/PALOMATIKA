@extends('layouts.miniapp')
@section('title', 'Панель дня — palomatika')

@push('styles')
  .mode-switch { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
  .lesson-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
  .attention-grid { display:grid; gap:10px; }
  .attention-card {
    border-radius:18px; padding:14px;
    background:linear-gradient(180deg, rgba(35,39,47,.94), rgba(28,31,39,.94));
    border:1px solid var(--border);
  }
  .attention-reason { margin-top:6px; font-size:12px; color:var(--muted); }
  .attention-actions { display:flex; gap:8px; margin-top:12px; }
  .lesson-students { display:flex; flex-direction:column; gap:10px; margin-top:14px; }
  .lesson-student {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:14px; border-radius:18px; background:rgba(35,39,47,.92); border:1px solid var(--border);
  }
  .lesson-student strong { display:block; color:var(--text); font-size:14px; }
  .lesson-student span { display:block; margin-top:4px; color:var(--muted); font-size:12px; }
@endpush

@section('body')
<div class="page page--teacher">
  @if($canSwitchMode)
    <div class="mode-switch">
      <form method="POST" action="/tg/mode/student">@csrf<button class="ghost-link" type="submit">Ученик</button></form>
      <form method="POST" action="/tg/mode/teacher">@csrf<button class="ghost-link" type="submit">Учитель</button></form>
    </div>
  @endif

  <section class="mini-hero">
    <div class="hero-kicker">Панель дня · {{ $todayLabel }}</div>
    <div class="hero-title">{{ $todayLessonCount }} урок{{ $todayLessonCount === 1 ? '' : ($todayLessonCount >= 2 && $todayLessonCount <= 4 ? 'а' : 'ов') }} сегодня</div>
    <div class="hero-subtitle">
      @if($featuredLesson)
        Ближайший рабочий слот: {{ $featuredLesson['time_start'] }}{{ $featuredLesson['time_end'] ? ' - ' . $featuredLesson['time_end'] : '' }} · {{ $featuredLesson['status_label'] }}
      @else
        На сегодня слотов пока нет. Можно открыть учеников и выдать домашку вручную.
      @endif
    </div>

    @if($featuredLesson)
      <div class="hero-stats">
        <div class="hero-stat">
          <div class="hero-stat-label">Слот</div>
          <div class="hero-stat-value">{{ $featuredLesson['time_start'] }}</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-label">Статус</div>
          <div class="hero-stat-value">{{ $featuredLesson['status_label'] }}</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-label">Ученики</div>
          <div class="hero-stat-value">{{ count($featuredLesson['students']) }}</div>
        </div>
      </div>
    @endif

    <div class="lesson-actions">
      <a href="/tg/teacher/lessons" class="mini-btn light">Открыть все уроки</a>
      <a href="/tg/teacher/homework" class="mini-btn">Дать ДЗ</a>
    </div>
  </section>

  <section class="section-card">
    <div class="section-head">
      <div>
        <div class="section-title">Текущий урок</div>
        <div class="section-note">Ученики выбранного слота и быстрые действия по ним.</div>
      </div>
      <a href="/tg/teacher/lessons" class="ghost-link">Открыть все уроки</a>
    </div>

    @if($featuredLesson)
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div>
          <strong style="display:block;color:var(--text);font-size:16px;">{{ $featuredLesson['time_start'] }}{{ $featuredLesson['time_end'] ? ' - ' . $featuredLesson['time_end'] : '' }}</strong>
          <span style="display:block;margin-top:4px;color:var(--muted);font-size:12px;">Слот дня · {{ $featuredLesson['status_label'] }}</span>
        </div>
        <span class="status-tag {{ $featuredLesson['status_key'] === 'current' ? 'green' : ($featuredLesson['status_key'] === 'past' ? 'red' : 'accent') }}">{{ $featuredLesson['status_label'] }}</span>
      </div>

      <div class="lesson-students">
        @foreach($featuredLesson['students'] as $student)
          <div class="lesson-student">
            <div>
              <strong>{{ $student['student_name'] }}</strong>
              <span>{{ $student['student_full_name'] ?: $student['evrium_name'] }} · {{ $student['risk_label'] }}</span>
            </div>
            <div class="lesson-actions" style="margin-top:0;">
              @if($student['student_id'])
                <a href="/tg/teacher/students/{{ $student['student_id'] }}" class="ghost-link">Профиль</a>
                <a href="/tg/teacher/homework" class="ghost-link">Дать ДЗ</a>
              @else
                <span class="status-tag red">Не привязан</span>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="note">Сегодня нет слотов в расписании. Как только появятся уроки из Evrium, они окажутся здесь с разбивкой по времени и статусу.</div>
    @endif
  </section>

  <section class="section-card">
    <div class="section-head">
      <div>
        <div class="section-title">Нуждаются во внимании</div>
        <div class="section-note">Ученики, по которым стоит сделать действие прямо сейчас.</div>
      </div>
      <a href="/tg/teacher/students?filter=risk" class="ghost-link">Все риски</a>
    </div>

    <div class="attention-grid">
      @forelse($attentionStudents as $student)
        <div class="attention-card">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <strong>{{ $student['name'] }}</strong>
            <span class="status-tag {{ $student['tone'] }}">{{ $student['reason'] }}</span>
          </div>
          <div class="attention-reason">{{ $student['subtitle'] }}</div>
          <div class="attention-actions">
            <a href="/tg/teacher/students/{{ $student['id'] }}" class="ghost-link">Профиль</a>
            <a href="/tg/teacher/homework" class="ghost-link">Выдать ДЗ</a>
          </div>
        </div>
      @empty
        <div class="note">Пока нет явных проблемных учеников. Можно перейти к урокам дня или открыть общий список класса.</div>
      @endforelse
    </div>
  </section>

  <section class="section-card">
    <div class="section-head">
      <div>
        <div class="section-title">Быстрые действия</div>
        <div class="section-note">Только актуальные teacher-сценарии без вариантов и лишних разделов.</div>
      </div>
    </div>
    <div class="action-grid">
      <a class="action-tile" href="/tg/teacher/lessons">
        <strong>Уроки сегодня</strong>
        <span>Все слоты, статусы `прошёл / идёт / будет` и ученики внутри.</span>
      </a>
      <a class="action-tile" href="/tg/teacher/students">
        <strong>Открыть учеников</strong>
        <span>Поиск, фильтры риска и быстрый вход в профиль.</span>
      </a>
      <a class="action-tile" href="/tg/teacher/homework">
        <strong>Выдать домашку</strong>
        <span>Перейти к назначению практики и контролю выполнения.</span>
      </a>
      <a class="action-tile" href="/tg/teacher/students?filter=scheduled">
        <strong>Ученики на сегодня</strong>
        <span>Быстрый доступ к тем, кто уже стоит в расписании.</span>
      </a>
    </div>
  </section>
</div>
@endsection
