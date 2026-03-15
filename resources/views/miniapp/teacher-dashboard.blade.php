@extends('layouts.miniapp')
@section('title', 'Сегодня — palomatika')

@push('styles')
  .today-topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; }
  .today-topbar .eyebrow { font-size:11px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:var(--muted); }
  .today-topbar .teacher-name { margin-top:4px; font-size:22px; font-weight:900; color:var(--text); }
  .mode-switch { display:flex; gap:8px; flex-wrap:wrap; }
  .lesson-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
  .mini-btn {
    display:inline-flex; align-items:center; justify-content:center;
    min-height:38px; padding:0 14px; border-radius:999px; text-decoration:none;
    font-size:12px; font-weight:800; border:1px solid var(--border);
    background:var(--surface2); color:var(--text);
  }
  .mini-btn.light { background:var(--accent); color:#fff; border-color:transparent; }
  .attention-grid { display:grid; gap:10px; }
  .attention-card {
    border-radius:18px; padding:14px;
    background:linear-gradient(180deg, rgba(35,39,47,.94), rgba(28,31,39,.94));
    border:1px solid var(--border);
  }
  .attention-reason { margin-top:6px; font-size:12px; color:var(--muted); }
  .attention-actions { display:flex; gap:8px; margin-top:12px; }
  .ghost-link {
    display:inline-flex; align-items:center; justify-content:center; text-decoration:none;
    min-height:36px; padding:0 12px; border-radius:12px;
    border:1px solid var(--border); color:var(--text); font-size:12px; font-weight:800;
    background:var(--surface2);
  }
  .status-tag {
    display:inline-flex; align-items:center; gap:6px;
    min-height:28px; padding:0 10px; border-radius:999px;
    font-size:11px; font-weight:800;
  }
  .status-tag.red { color:#b42318; background:#fff0ef; }
  .status-tag.yellow { color:#a15c00; background:#fff7e6; }
  .status-tag.green { color:#0f7b45; background:#ebfff4; }
  .status-tag.accent { color:#1d4ed8; background:#ebf3ff; }
@endpush

@section('body')
<div class="page page--teacher">
  <div class="today-topbar">
    <div>
      <div class="eyebrow">Учительский mini app</div>
      <div class="teacher-name">Сегодня</div>
    </div>
    @if($canSwitchMode)
      <div class="mode-switch">
        <form method="POST" action="/tg/mode/student">@csrf<button class="ghost-link" type="submit">Ученик</button></form>
        <form method="POST" action="/tg/mode/teacher">@csrf<button class="ghost-link" type="submit">Учитель</button></form>
      </div>
    @endif
  </div>

  <section class="mini-hero">
    <div class="hero-kicker">Панель дня · {{ $todayLabel }}</div>
    <div class="hero-title">Урок, ученики и домашка без лишних экранов</div>
    <div class="hero-subtitle">Сначала видишь ближайшие действия, потом открываешь профиль ученика или выдаёшь практику в один-два тапа.</div>
    <div class="hero-stats">
      <div class="hero-stat">
        <div class="hero-stat-label">Уроков сегодня</div>
        <div class="hero-stat-value">{{ $todayLessonCount }}</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-label">Мои ученики</div>
        <div class="hero-stat-value">{{ $studentCount }}</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-label">Варианты</div>
        <div class="hero-stat-value">{{ $variantsCount }}</div>
      </div>
    </div>
    <div class="lesson-actions">
      <a href="/tg/teacher/homework" class="mini-btn light">Выдать ДЗ</a>
      <a href="/tg/teacher/students" class="mini-btn">Открыть учеников</a>
    </div>
  </section>

  <section class="section-card">
    <div class="section-head">
      <div>
        <div class="section-title">Текущий урок</div>
        <div class="section-note">Ближайшие ученики из расписания и быстрые действия.</div>
      </div>
      <a href="/tg/teacher/homework" class="ghost-link">К домашке</a>
    </div>

    <div class="mini-list">
      @forelse($currentStudents as $student)
        <div class="mini-list-item">
          <div>
            <strong>{{ $student['student_alias'] ?? $student['student_name'] ?? $student['evrium_name'] }}</strong>
            <span>
              {{ $student['time_start'] ?: 'Сегодня' }}{{ $student['time_end'] ? ' - ' . $student['time_end'] : '' }}
              · {{ $student['linked'] ? ($student['evrium_name'] ?: 'привязан') : 'не привязан' }}
            </span>
          </div>
          <div class="lesson-actions" style="margin-top:0;">
            @if(!empty($student['student_id']))
              <a href="/tg/teacher/students/{{ $student['student_id'] }}" class="ghost-link">Профиль</a>
              <a href="/tg/teacher/homework" class="ghost-link">Дать ДЗ</a>
            @else
              <span class="status-tag red">Нужна привязка</span>
            @endif
          </div>
        </div>
      @empty
        <div class="note">Сегодня в расписании пока нет связанных уроков. Открой раздел домашки, чтобы назначить практику вручную или связать учеников с Evrium.</div>
      @endforelse
    </div>
  </section>

  <section class="section-card">
    <div class="section-head">
      <div>
        <div class="section-title">Нуждаются во внимании</div>
        <div class="section-note">Не отчёт ради отчёта, а список учеников, по которым стоит сделать действие.</div>
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
        <div class="note">Пока нет явных проблемных учеников. Можно перейти к выдаче домашки или открыть общий список класса.</div>
      @endforelse
    </div>
  </section>

  <section class="section-card">
    <div class="section-head">
      <div>
        <div class="section-title">Быстрые действия</div>
        <div class="section-note">Частые сценарии упакованы в крупные мобильные точки входа.</div>
      </div>
    </div>
    <div class="action-grid">
      <a class="action-tile" href="/tg/teacher/homework">
        <strong>Выдать домашку</strong>
        <span>Полный вариант или тема с нижней панели контроля.</span>
      </a>
      <a class="action-tile" href="/tg/teacher/students">
        <strong>Открыть учеников</strong>
        <span>Поиск, фильтры риска и быстрый вход в профиль.</span>
      </a>
      <a class="action-tile" href="/tg/teacher/variants">
        <strong>Мои варианты</strong>
        <span>{{ $curatedCount }} кураторских и {{ $variantsCount }} всего.</span>
      </a>
      <a class="action-tile" href="/tg/admin/variants">
        <strong>Создать вариант</strong>
        <span>Быстрый вход в генерацию без отдельного дашборда.</span>
      </a>
    </div>
  </section>

  <section class="section-card">
    <div class="section-head">
      <div>
        <div class="section-title">Последние варианты</div>
        <div class="section-note">Для быстрого возвращения к недавним материалам.</div>
      </div>
      <a href="/tg/teacher/variants" class="ghost-link">Все</a>
    </div>
    <div class="mini-list">
      @forelse($recentVariants as $variant)
        <div class="mini-list-item">
          <div>
            <strong>{{ $variant->title ?: ('Вариант ' . $variant->hash) }}</strong>
            <span>{{ $variant->created_at?->format('d.m.Y H:i') }} · {{ $variant->is_curated ? 'Кураторский' : 'Генератор' }}</span>
          </div>
          <span class="status-tag accent">{{ $variant->hash }}</span>
        </div>
      @empty
        <div class="note">Пока нет созданных вариантов.</div>
      @endforelse
    </div>
  </section>
</div>
@endsection
