@extends('layouts.miniapp')

@section('title', 'Профиль ученика')

@push('styles')
  .profile-stack { display:flex; flex-direction:column; gap:16px; }
  .profile-head-meta { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
  .profile-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
  .profile-actions a { text-decoration:none; }
  .kpi-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
  .kpi-card {
    padding:14px; border-radius:18px; background:linear-gradient(180deg, var(--surface), var(--surface2));
    border:1px solid var(--border);
  }
  .kpi-label { font-size:11px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; }
  .kpi-value { margin-top:6px; font-size:24px; font-weight:900; color:var(--text); }
  .kpi-meta { margin-top:4px; font-size:12px; color:var(--muted); }
  .priority-list { display:flex; flex-direction:column; gap:10px; }
  .priority-card {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:14px; border-radius:18px; background:var(--surface2); border:1px solid var(--border);
  }
  .priority-card strong { display:block; color:var(--text); font-size:14px; }
  .priority-card span { display:block; margin-top:4px; font-size:12px; color:var(--muted); }
  .timeline { display:flex; flex-direction:column; gap:10px; }
  .timeline-item {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:14px; border-radius:18px; background:var(--surface2); border:1px solid var(--border);
    text-decoration:none; color:inherit;
  }
  .timeline-score { font-size:24px; font-weight:900; color:var(--text); white-space:nowrap; }
  .timeline-score small { font-size:12px; color:var(--muted); }
@endpush

@section('body')
<div class="page page--teacher">
  <div class="profile-stack">
    <a href="/tg/teacher/students" class="ghost-link" style="width:max-content;">← К ученикам</a>

    <section class="mini-hero">
      <div class="hero-kicker">Профиль ученика</div>
      <div class="hero-title">{{ $teacherRelation?->student_alias ?: $student->name }}</div>
      <div class="hero-subtitle">
        {{ $student->name ?: 'Без имени' }}
        @if($student->email)
          · {{ $student->email }}
        @endif
      </div>
      <div class="profile-head-meta">
        @if($teacherRelation?->evrium_name)
          <span class="status-tag green">{{ $teacherRelation->evrium_name }}</span>
        @else
          <span class="status-tag red">Нет привязки к расписанию</span>
        @endif
        <span class="status-tag accent">ID {{ $student->id }}</span>
      </div>
      <div class="profile-actions">
        <a href="/tg/teacher/homework" class="mini-btn light">Дать ДЗ</a>
        <a href="/tg/teacher/students" class="mini-btn">К списку</a>
      </div>
    </section>

    <section class="section-card">
      <div class="section-head">
        <div>
          <div class="section-title">Ключевые сигналы</div>
          <div class="section-note">Не вся аналитика сразу, а то, что помогает решить следующий шаг.</div>
        </div>
      </div>
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">Точность</div>
          <div class="kpi-value">{{ $accuracy === null ? '—' : $accuracy . '%' }}</div>
          <div class="kpi-meta">{{ $correctTotal }}/{{ $scoredTotal }} проверенных ответов</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Попытки</div>
          <div class="kpi-value">{{ $attempts->count() }}</div>
          <div class="kpi-meta">Завершённые и активные решения</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Последний результат</div>
          <div class="kpi-value">{{ count($historyList) ? ($historyList[0]['correct'] . '/' . $historyList[0]['total']) : '—' }}</div>
          <div class="kpi-meta">{{ count($historyList) && $historyList[0]['date'] ? $historyList[0]['date']->format('d.m.Y H:i') : 'Пока нет завершённых попыток' }}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Домашка</div>
          <div class="kpi-value">{{ $homeworkHistory->count() }}</div>
          <div class="kpi-meta">Последних назначений в истории</div>
        </div>
      </div>
    </section>

    <section class="section-card">
      <div class="section-head">
        <div>
          <div class="section-title">Слабые темы</div>
          <div class="section-note">Главный блок для решения, что назначить дальше.</div>
        </div>
      </div>
      <div class="priority-list">
        @forelse($weakTopics as $topic)
          <div class="priority-card">
            <div>
              <strong>Задание {{ $topic['task_number'] }}</strong>
              <span>{{ $topic['correct'] }}/{{ $topic['total'] }} верно · прицельная практика по теме</span>
            </div>
            <span class="status-tag {{ $topic['tone'] }}">{{ $topic['accuracy'] }}%</span>
          </div>
        @empty
          <div class="note">Пока нет данных по слабым темам. Как только ученик решит и будет проверка, здесь появятся приоритеты.</div>
        @endforelse
      </div>
    </section>

    <section class="section-card">
      <div class="section-head">
        <div>
          <div class="section-title">Последние попытки</div>
          <div class="section-note">История решений в виде мобильной ленты.</div>
        </div>
      </div>
      <div class="timeline">
        @forelse($historyList as $h)
          <a href="/tg/teacher/students/{{ $student->id }}/attempt/{{ $h['id'] }}" class="timeline-item">
            <div>
              <strong>{{ $h['label'] }}</strong>
              <span>
                {{ $h['date']?->format('d.m.Y H:i') ?: 'Без даты' }}
                @if($h['hash']) · {{ $h['hash'] }} @endif
              </span>
            </div>
            <div class="timeline-score">{{ $h['correct'] }}<small>/{{ $h['total'] }}</small></div>
          </a>
        @empty
          <div class="note">Пока нет завершённых попыток.</div>
        @endforelse
      </div>
    </section>

    <section class="section-card">
      <div class="section-head">
        <div>
          <div class="section-title">История домашних заданий</div>
          <div class="section-note">Назначено, начато или завершено.</div>
        </div>
        <a href="/tg/teacher/homework" class="ghost-link">Выдать ДЗ</a>
      </div>
      <div class="priority-list">
        @forelse($homeworkHistory as $hw)
          <div class="priority-card">
            <div>
              <strong>{{ $hw['title'] }}</strong>
              <span>{{ $hw['subtitle'] }}</span>
            </div>
            <span class="status-tag {{ $hw['status'] === 'completed' ? 'green' : ($hw['status'] === 'started' ? 'yellow' : 'accent') }}">{{ $hw['status'] }}</span>
          </div>
        @empty
          <div class="note">История домашних заданий пока пустая.</div>
        @endforelse
      </div>
    </section>
  </div>
</div>
@endsection
