@extends('layouts.miniapp')
@section('title', 'Ученики — palomatika')

@push('styles')
  .students-shell { display:flex; flex-direction:column; gap:16px; }
  .students-search {
    display:flex; align-items:center; gap:10px; padding:14px 16px;
    border-radius:20px; background:var(--surface); border:1px solid var(--border);
    box-shadow:0 12px 34px rgba(0,0,0,.2);
  }
  .students-search input {
    flex:1; border:none; background:transparent; color:var(--text); font-size:14px; font-weight:700; outline:none;
  }
  .students-search button {
    border:none; min-height:40px; padding:0 16px; border-radius:14px;
    background:var(--accent); color:#fff; font-size:12px; font-weight:800;
  }
  .students-grid { display:flex; flex-direction:column; gap:12px; }
  .student-card {
    padding:16px; border-radius:22px; background:var(--surface);
    border:1px solid var(--border); box-shadow:0 12px 34px rgba(0,0,0,.2);
  }
  .student-card-head { display:flex; align-items:flex-start; gap:12px; }
  .student-avatar, .student-avatar-fallback {
    width:46px; height:46px; border-radius:16px; object-fit:cover; flex-shrink:0;
    border:1px solid var(--accent-bd); background:linear-gradient(135deg, rgba(167,139,250,.2), rgba(79,142,247,.18));
  }
  .student-avatar-fallback {
    display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:900; color:var(--accent);
  }
  .student-card-name { font-size:16px; font-weight:900; color:var(--text); }
  .student-card-subtitle { margin-top:4px; font-size:12px; color:var(--muted); line-height:1.45; }
  .student-card-meta { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
  .student-card-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
  .student-settings {
    margin-top:14px; padding-top:14px; border-top:1px solid var(--border);
    display:flex; flex-direction:column; gap:10px;
  }
  .student-settings label { font-size:11px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; }
  .student-settings input, .student-settings select {
    width:100%; min-height:42px; padding:0 12px; border-radius:14px;
    border:1px solid var(--border); background:var(--surface2); color:var(--text); font-size:13px; font-weight:700;
  }
  .student-settings-actions { display:flex; gap:8px; }
  .student-settings-actions button, .student-settings-actions a {
    flex:1; min-height:42px; border-radius:14px; text-decoration:none; font-size:12px; font-weight:800;
    display:flex; align-items:center; justify-content:center;
  }
  .student-settings-actions .save-btn { border:none; background:var(--accent); color:#fff; }
  .student-settings-actions .cancel-btn { border:1px solid var(--border); background:var(--surface2); color:var(--text); }
@endpush

@section('body')
<div class="page page--teacher" x-data="studentsPage()">
  <div class="students-shell">
    <section class="mini-hero">
      <div class="hero-kicker">Ученики</div>
      <div class="hero-title">Профили, риски и быстрый вход в действия</div>
      <div class="hero-subtitle">Список работает как мобильная CRM: сначала поиск и сигналы, настройки алиаса и привязки спрятаны во вторичный поток.</div>
    </section>

    <form method="GET" action="/tg/teacher/students" class="students-search">
      <input type="text" name="search" value="{{ $search }}" placeholder="Имя, email или алиас">
      <input type="hidden" name="filter" value="{{ $filter }}">
      <button type="submit">Поиск</button>
    </form>

    <div class="chip-row">
      <a href="/tg/teacher/students?search={{ urlencode($search) }}&filter=all" class="chip {{ $filter === 'all' ? 'active' : '' }}">Все</a>
      <a href="/tg/teacher/students?search={{ urlencode($search) }}&filter=mine" class="chip {{ $filter === 'mine' ? 'active' : '' }}">Мои</a>
      <a href="/tg/teacher/students?search={{ urlencode($search) }}&filter=scheduled" class="chip {{ $filter === 'scheduled' ? 'active' : '' }}">На уроке</a>
      <a href="/tg/teacher/students?search={{ urlencode($search) }}&filter=risk" class="chip {{ $filter === 'risk' ? 'active' : '' }}">Есть риск</a>
      <a href="/tg/teacher/students?search={{ urlencode($search) }}&filter=unlinked" class="chip {{ $filter === 'unlinked' ? 'active' : '' }}">Без привязки</a>
    </div>

    <div class="students-grid">
      @forelse($students as $student)
        <article class="student-card" x-data="{ editing: false }" data-student-id="{{ $student->id }}">
          <div class="student-card-head">
            @if(!empty($student->avatar))
              <img class="student-avatar" src="{{ $student->avatar }}" alt="avatar">
            @else
              <div class="student-avatar-fallback">{{ mb_substr($student->name ?: 'У', 0, 1) }}</div>
            @endif

            <div style="flex:1;min-width:0;">
              <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div>
                  <div class="student-card-name">{{ $student->student_alias ?: $student->name }}</div>
                  <div class="student-card-subtitle">
                    {{ $student->name }}
                    @if($student->email)
                      · {{ $student->email }}
                    @endif
                  </div>
                </div>
                <span class="status-tag {{ $student->risk_tone }}">{{ $student->risk_label }}</span>
              </div>

              <div class="student-card-meta">
                @if($student->is_scheduled_today)
                  <span class="status-tag accent">На уроке</span>
                @endif
                @if($student->evrium_name)
                  <span class="status-tag green">{{ $student->evrium_name }}</span>
                @else
                  <span class="status-tag red">Нет Evrium</span>
                @endif
                @if($student->is_mine)
                  <span class="status-tag green">Мой ученик</span>
                @else
                  <span class="status-tag yellow">Не помечен</span>
                @endif
              </div>
            </div>
          </div>

          <div class="student-card-actions">
            <a class="ghost-link" href="/tg/teacher/students/{{ $student->id }}">Открыть профиль</a>
            <a class="ghost-link" href="/tg/teacher/homework">Дать ДЗ</a>
            <button class="ghost-link" type="button" @click="toggleOwnership({{ $student->id }})" x-text="isMine[{{ $student->id }}] ? 'Не мой' : 'Сделать моим'"></button>
            <button class="ghost-link" type="button" @click="editing = !editing" x-text="editing ? 'Скрыть настройки' : 'Настроить'"></button>
          </div>

          <div class="student-settings" x-show="editing" x-cloak>
            <div>
              <label>Алиас</label>
              <input type="text" x-model="aliases[{{ $student->id }}]" :disabled="!isMine[{{ $student->id }}]" placeholder="Как показывать ученика в кабинете">
            </div>
            <div>
              <label>Привязка к Evrium</label>
              <input type="text" x-model="evriumNames[{{ $student->id }}]" :disabled="!isMine[{{ $student->id }}]" placeholder="Имя ученика в расписании">
            </div>
            <div class="student-settings-actions">
              <a href="/tg/teacher/students/{{ $student->id }}" class="cancel-btn">В профиль</a>
              <button class="save-btn" type="button" :disabled="!isMine[{{ $student->id }}]" @click="saveSettings({{ $student->id }})">Сохранить</button>
            </div>
          </div>
        </article>
      @empty
        <div class="note">Список пуст. Попробуй изменить поиск или фильтр.</div>
      @endforelse
    </div>

    @if($students->hasPages())
      <div class="pager">{{ $students->links() }}</div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
function studentsPage() {
  return {
    aliases: @json($students->getCollection()->mapWithKeys(fn($s) => [$s->id => $s->student_alias])->all()),
    isMine: @json($students->getCollection()->mapWithKeys(fn($s) => [$s->id => (bool)($s->is_mine ?? false)])->all()),
    evriumNames: @json($students->getCollection()->mapWithKeys(fn($s) => [$s->id => $s->evrium_name])->all()),

    async toggleOwnership(studentId) {
      const res = await fetch(`/tg/teacher/students/${studentId}/ownership`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': window._csrf,
        },
      });

      if (!res.ok) {
        const tg = window.Telegram?.WebApp;
        if (tg?.showAlert) tg.showAlert('Не удалось изменить статус ученика');
        return;
      }

      const data = await res.json();
      this.isMine[studentId] = !!data.is_mine;
      if (!this.isMine[studentId]) {
        this.aliases[studentId] = '';
        this.evriumNames[studentId] = '';
      }
    },

    async saveSettings(studentId) {
      if (!this.isMine[studentId]) return;

      const alias = (this.aliases[studentId] || '').trim();
      const evrium_name = (this.evriumNames[studentId] || '').trim();

      const res = await fetch(`/tg/teacher/students/${studentId}/link`, {
        method: 'PATCH',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window._csrf,
        },
        body: JSON.stringify({ alias, evrium_name }),
      });

      if (!res.ok) {
        const tg = window.Telegram?.WebApp;
        if (tg?.showAlert) tg.showAlert('Не удалось сохранить настройки');
        return;
      }

      const tg = window.Telegram?.WebApp;
      if (tg?.showAlert) tg.showAlert('Настройки сохранены');
    }
  };
}
</script>
@endpush
