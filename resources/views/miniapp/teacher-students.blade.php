@extends('layouts.miniapp')
@section('title', 'Ученики — palomatika')

@push('styles')
  .filters { display: flex; gap: 6px; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 2px; }
  .filter-btn {
    padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 700;
    white-space: nowrap; border: 1px solid var(--border); background: var(--surface);
    color: var(--muted); cursor: pointer; text-decoration: none; text-align: center;
  }
  .filter-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
  .search-row { display: flex; gap: 8px; }
  .search-row input {
    flex: 1; background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 11px 14px; color: var(--text); font-size: 13px;
  }
  .student-list { display: flex; flex-direction: column; gap: 8px; }

  .s-card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
    overflow: hidden; text-decoration: none; color: inherit; display: block;
  }
  .s-card-main { display: flex; align-items: center; gap: 12px; padding: 14px; }
  .s-avatar {
    width: 38px; height: 38px; border-radius: 999px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800; text-transform: uppercase;
    border: 1px solid var(--border);
  }
  .s-avatar img { width: 100%; height: 100%; border-radius: 999px; object-fit: cover; }
  .s-avatar-fb { background: #1f2937; color: #cbd5e1; }
  .s-info { flex: 1; min-width: 0; }
  .s-name {
    font-size: 14px; font-weight: 800; color: var(--text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .s-original {
    font-size: 11px; color: var(--muted); margin-top: 1px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .s-stats { display: flex; gap: 10px; margin-top: 3px; }
  .s-stat { font-size: 11px; font-weight: 700; color: var(--muted); }
  .s-stat b { color: var(--text); }
  .s-accuracy-good b { color: var(--green, #22c55e); }
  .s-accuracy-mid b { color: var(--yellow, #eab308); }
  .s-accuracy-bad b { color: var(--red, #ef4444); }
  .s-chevron { color: var(--muted); font-size: 18px; flex-shrink: 0; opacity: .5; }

  .s-card-footer {
    border-top: 1px solid var(--border); padding: 10px 14px;
    display: flex; align-items: center; gap: 8px;
  }
  .alias-input {
    flex: 1; background: var(--surface2); border: 1px solid var(--border);
    border-radius: 8px; padding: 8px 10px; color: var(--text); font-size: 12px;
  }
  .alias-input::placeholder { color: var(--muted); }
  .btn-save-alias {
    padding: 8px 12px; border-radius: 8px; font-size: 11px; font-weight: 800;
    border: none; cursor: pointer; transition: opacity .15s;
  }
  .btn-save-alias:active { opacity: .7; }
  .btn-save-alias.idle { background: var(--surface2); color: var(--muted); }
  .btn-save-alias.dirty { background: var(--accent); color: #fff; }
  .btn-save-alias.saved { background: var(--green, #22c55e); color: #0e1a12; }

  .mine-toggle {
    font-size: 11px; font-weight: 700; color: var(--muted); cursor: pointer;
    background: none; border: none; padding: 4px 0;
  }
  .mine-toggle:hover { color: var(--text); }

  .day-picker {
    display: flex; gap: 4px; justify-content: space-between;
  }
  .day-btn {
    flex: 1; text-align: center; padding: 9px 0; border-radius: 10px;
    font-size: 12px; font-weight: 800; color: var(--muted);
    background: var(--surface); border: 1px solid var(--border);
    text-decoration: none; transition: background .15s, color .15s;
  }
  .day-btn.today { border-color: var(--accent); color: var(--accent); }
  .day-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
  .pager { display: flex; justify-content: center; margin-top: 4px; }
  .empty-msg { text-align: center; padding: 30px 16px; color: var(--muted); font-size: 13px; }
@endpush

@section('body')
<div class="page" x-data="studentsPage()">
  <div class="topbar">
    <a href="/tg/teacher/dashboard" class="back-btn">‹</a>
    <div class="topbar-title">Ученики</div>
  </div>

  <div class="filters">
    <a class="filter-btn {{ $filter === 'mine' ? 'active' : '' }}" href="?filter=mine{{ $search ? '&search='.urlencode($search) : '' }}">Мои</a>
    <a class="filter-btn {{ $filter === 'all' ? 'active' : '' }}" href="?filter=all{{ $search ? '&search='.urlencode($search) : '' }}">Все</a>
    <a class="filter-btn {{ $filter === 'scheduled' ? 'active' : '' }}" href="?filter=scheduled{{ $search ? '&search='.urlencode($search) : '' }}">По расписанию</a>
  </div>

  @if($filter === 'scheduled')
    <div class="day-picker">
      @foreach($dayNames as $dow => $label)
        <a class="day-btn {{ $dow === $selectedDay ? 'active' : '' }} {{ $dow === $todayDow ? 'today' : '' }}"
           href="?filter=scheduled&day={{ $dow }}{{ $search ? '&search='.urlencode($search) : '' }}">{{ $label }}</a>
      @endforeach
    </div>
  @endif

  <form method="GET" action="/tg/teacher/students" class="search-row">
    <input type="hidden" name="filter" value="{{ $filter }}">
    <input type="text" name="search" value="{{ $search }}" placeholder="Поиск по имени или алиасу">
    <button type="submit" class="btn btn-surface" style="font-size:12px;">Найти</button>
  </form>

  <div class="student-list">
    @forelse($students as $student)
      @php
        $hasAlias = !blank($student->student_alias);
        $displayName = $hasAlias ? $student->student_alias : $student->name;
        $initials = mb_substr($displayName, 0, 1);
        $accClass = $student->accuracy === null ? '' :
          ($student->accuracy >= 70 ? 's-accuracy-good' : ($student->accuracy >= 40 ? 's-accuracy-mid' : 's-accuracy-bad'));
      @endphp
      <div class="s-card" data-sid="{{ $student->id }}">
        <a href="/tg/teacher/students/{{ $student->id }}" class="s-card-main">
          <div class="s-avatar {{ empty($student->avatar) ? 's-avatar-fb' : '' }}">
            @if(!empty($student->avatar))
              <img src="{{ $student->avatar }}" alt="">
            @else
              {{ $initials }}
            @endif
          </div>
          <div class="s-info">
            <div class="s-name" x-text="displayName({{ $student->id }})">{{ $displayName }}</div>
            @if($hasAlias)
              <div class="s-original">{{ $student->name }}</div>
            @else
              <div class="s-original" x-show="aliases[{{ $student->id }}] && aliases[{{ $student->id }}].trim() !== ''" x-text="'← ' + '{{ e($student->name) }}'"></div>
            @endif
            <div class="s-stats">
              @if($student->attempt_count > 0)
                <span class="s-stat"><b>{{ $student->attempt_count }}</b> попыт.</span>
                @if($student->accuracy !== null)
                  <span class="s-stat {{ $accClass }}"><b>{{ $student->accuracy }}%</b> точн.</span>
                @endif
              @else
                <span class="s-stat">нет попыток</span>
              @endif
              @if($student->last_attempt_at)
                <span class="s-stat">{{ $student->last_attempt_at->diffForHumans(short: true) }}</span>
              @endif
            </div>
          </div>
          <div class="s-chevron">›</div>
        </a>
        <div class="s-card-footer" x-data="{ saved: false }">
          <input class="alias-input" type="text"
                 x-model="aliases[{{ $student->id }}]"
                 placeholder="Имя для ученика (только вам видно)"
                 @keydown.enter.prevent="saveAlias({{ $student->id }}); saved = true; setTimeout(() => saved = false, 1500)">
          <button class="btn-save-alias"
                  :class="saved ? 'saved' : (aliasDirty({{ $student->id }}) ? 'dirty' : 'idle')"
                  @click="saveAlias({{ $student->id }}); saved = true; setTimeout(() => saved = false, 1500)"
                  x-text="saved ? '✓' : 'Сохр.'"></button>
          @if(!(bool)($student->is_mine ?? false))
            <button class="mine-toggle" @click="claimStudent({{ $student->id }})">+ Мой</button>
          @endif
        </div>
      </div>
    @empty
      <div class="empty-msg">
        @if($search)
          Ничего не найдено по «{{ $search }}»
        @elseif($filter === 'mine')
          У вас ещё нет привязанных учеников
        @else
          Список пуст
        @endif
      </div>
    @endforelse
  </div>

  @if($students->hasPages())
    <div class="pager">{{ $students->links() }}</div>
  @endif
</div>
@endsection

@push('scripts')
<script>
function studentsPage() {
  return {
    aliases: @json($students->getCollection()->mapWithKeys(fn($s) => [$s->id => $s->student_alias ?? ''])->all()),
    origAliases: @json($students->getCollection()->mapWithKeys(fn($s) => [$s->id => $s->student_alias ?? ''])->all()),
    names: @json($students->getCollection()->mapWithKeys(fn($s) => [$s->id => $s->name])->all()),

    displayName(id) {
      const alias = (this.aliases[id] || '').trim();
      return alias || this.names[id] || '?';
    },

    aliasDirty(id) {
      return (this.aliases[id] || '').trim() !== (this.origAliases[id] || '').trim();
    },

    async saveAlias(studentId) {
      const alias = (this.aliases[studentId] || '').trim();
      const res = await fetch(`/tg/teacher/students/${studentId}/alias`, {
        method: 'PATCH',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window._csrf,
        },
        body: JSON.stringify({ alias }),
      });
      if (!res.ok) {
        const tg = window.Telegram?.WebApp;
        if (tg?.showAlert) tg.showAlert('Не удалось сохранить');
        return;
      }
      const data = await res.json();
      this.aliases[studentId] = data.alias || '';
      this.origAliases[studentId] = data.alias || '';
    },

    async claimStudent(studentId) {
      const res = await fetch(`/tg/teacher/students/${studentId}/ownership`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window._csrf },
      });
      if (res.ok) {
        location.reload();
      }
    }
  };
}
</script>
@endpush
