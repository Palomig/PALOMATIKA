@extends('layouts.pwa')
@section('title', 'Домашка — palomatika')

@push('styles')
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
  }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 10px; }
  .topbar-title { font-family: var(--display); font-size: 18px; color: var(--text); }

  .hw-tabs {
    display: flex; gap: 6px; margin-bottom: 14px;
    opacity: 0; animation: fadeDown 0.3s ease 0.05s forwards;
  }
  .hw-tab {
    flex: 1; text-align: center; padding: 9px 6px;
    font-size: 12px; font-weight: 800; color: var(--muted);
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 10px; cursor: pointer; transition: all 0.2s;
  }
  .hw-tab:active { opacity: 0.7; }
  .hw-tab.active {
    color: var(--accent); background: var(--accent-bg);
    border-color: var(--accent-bd);
  }
  .hw-tab-sub { font-size: 10px; font-weight: 600; color: var(--muted); margin-top: 2px; }
  .hw-tab.active .hw-tab-sub { color: var(--accent); opacity: 0.7; }

  .student-row {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 8px;
    opacity: 0; animation: fadeUp 0.3s ease calc(var(--i, 0) * 0.04s) forwards;
  }
  .student-name { font-size: 14px; font-weight: 700; color: var(--text); }
  .student-sub { font-size: 11px; color: var(--muted); font-weight: 600; }
  .student-unlinked { opacity: 0.5; }
  .student-unlinked .student-name { font-style: italic; }
  .assign-btn {
    font-size: 11px; font-weight: 800; color: var(--purple);
    background: var(--purple-bg); border: 1px solid var(--purple-bd);
    border-radius: 8px; padding: 6px 12px; cursor: pointer;
    white-space: nowrap;
  }
  .assign-btn:active { opacity: 0.7; }
  .link-badge {
    font-size: 9px; font-weight: 800; color: var(--muted);
    background: var(--surface2); border-radius: 5px;
    padding: 2px 6px; margin-left: 6px;
  }

  .settings-btn {
    font-size: 14px; padding: 4px 8px; cursor: pointer;
    background: none; border: none; color: var(--muted);
  }

  .hw-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px; margin-bottom: 8px;
  }
  .hw-title { font-size: 13px; font-weight: 700; color: var(--text); }
  .hw-meta { font-size: 11px; color: var(--muted); font-weight: 600; margin-top: 2px; }
  .hw-status-badge {
    display: inline-block; font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.06em;
    padding: 2px 7px; border-radius: 5px; margin-top: 4px;
  }
  .badge-assigned { background: rgba(59,130,246,.2); color: #93bbfd; }
  .badge-started { background: rgba(234,179,8,.2); color: #fcd34d; }
  .badge-completed { background: rgba(34,197,94,.2); color: #86efac; }

  .toast {
    position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
    background: var(--green); color: #000; font-weight: 700; font-size: 13px;
    padding: 10px 20px; border-radius: 10px; z-index: 200;
    animation: fadeUp 0.3s ease forwards;
  }
  .toast-error { background: var(--red); color: #fff; }

  .empty-note { text-align: center; padding: 24px; color: var(--muted); font-size: 13px; font-weight: 600; }

  .link-form { margin-top: 8px; }
  .link-form label { display: block; font-size: 11px; font-weight: 700; color: var(--muted); margin-bottom: 4px; }
  .link-form input, .link-form select {
    width: 100%; padding: 8px 10px; border-radius: 8px;
    border: 1px solid var(--border); background: var(--surface);
    color: var(--text); font-size: 13px; margin-bottom: 8px;
  }
  .link-form .link-save {
    font-size: 12px; font-weight: 800; color: #fff;
    background: var(--accent); border: none; border-radius: 8px;
    padding: 8px 16px; cursor: pointer; width: 100%;
  }
  .link-form .link-save:active { opacity: 0.7; }

  .fv-overlay {
    position: fixed; inset: 0; z-index: 100;
    background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
    display: flex; align-items: flex-end; justify-content: center;
  }
  .fv-sheet {
    background: var(--bg); border-radius: 20px 20px 0 0;
    width: 100%; max-width: 420px; padding: 20px 20px 32px;
  }
  .fv-handle {
    width: 36px; height: 4px; background: var(--border);
    border-radius: 2px; margin: 0 auto 16px;
  }
  .fv-title {
    font-family: var(--display); font-size: 18px; color: var(--text);
    text-align: center; margin-bottom: 16px;
  }
  .fv-option {
    display: flex; align-items: center; gap: 14px;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--r); padding: 16px;
    margin-bottom: 10px; cursor: pointer; transition: border-color 0.15s;
    text-decoration: none; color: inherit;
  }
  .fv-option:active { background: var(--surface2); }
  .fv-opt-icon { font-size: 28px; flex-shrink: 0; }
  .fv-opt-title { font-family: var(--display); font-size: 15px; color: var(--text); }
  .fv-opt-desc { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; line-height: 1.3; }
  .student-pick-list {
    max-height: 160px; overflow-y: auto; display: grid; gap: 6px;
    margin-top: 8px; padding-right: 2px;
  }
  .student-pick {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-radius: 9px;
    background: var(--surface); border: 1px solid var(--border);
    color: var(--text); font-size: 12px; font-weight: 700;
  }
@endpush

@section('body')
<div class="page" x-data="teacherHw()">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.dashboard') }}" class="back">←</a>
    <div class="topbar-title">Домашка</div>
    <div style="width:34px;"></div>
  </div>

  @if(session('success'))
    <div class="toast" x-init="setTimeout(() => $el.remove(), 3000)">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="toast toast-error" x-init="setTimeout(() => $el.remove(), 3000)">{{ session('error') }}</div>
  @endif

  <div class="hw-tabs">
    <div class="hw-tab" :class="tab === 'current' && 'active'" @click="tab = 'current'">
      <div>Сейчас</div>
      <div class="hw-tab-sub">{{ $todayLabel }}</div>
    </div>
    <div class="hw-tab" :class="tab === 'prev' && 'active'" @click="tab = 'prev'">
      <div>Прошлый</div>
      <div class="hw-tab-sub">{{ $prevDayLabel ?: '—' }}</div>
    </div>
    <div class="hw-tab" :class="tab === 'all' && 'active'" @click="tab = 'all'">
      <div>Все</div>
      <div class="hw-tab-sub">{{ $allStudents->count() }} уч.</div>
    </div>
  </div>

  <div x-show="tab === 'current'" x-cloak>
    @forelse($currentStudents as $i => $s)
      <div class="student-row {{ $s['linked'] ? '' : 'student-unlinked' }}" style="--i:{{ $i }}">
        <div>
          <div class="student-name">
            @if($s['linked'])
              {{ $s['student_alias'] ?? $s['student_name'] }}
            @else
              {{ $s['evrium_name'] }}
            @endif
          </div>
          <div class="student-sub">{{ $s['time_start'] }}{{ $s['time_end'] ? ' – ' . $s['time_end'] : '' }}
            @if($s['linked'])
              <span class="link-badge">{{ $s['evrium_name'] }}</span>
            @else
              <span class="link-badge" style="color:var(--red);">не привязан</span>
            @endif
          </div>
        </div>
        @if($s['linked'])
          <button class="assign-btn" @click="openAssign({{ $s['student_id'] }}, '{{ e($s['student_alias'] ?? $s['student_name'] ?? '') }}')">Дать ДЗ</button>
        @endif
      </div>
    @empty
      <div class="empty-note">Нет уроков на сегодня.</div>
    @endforelse
  </div>

  <div x-show="tab === 'prev'" x-cloak>
    @forelse($prevStudents as $i => $s)
      <div class="student-row {{ $s['linked'] ? '' : 'student-unlinked' }}" style="--i:{{ $i }}">
        <div>
          <div class="student-name">
            @if($s['linked'])
              {{ $s['student_alias'] ?? $s['student_name'] }}
            @else
              {{ $s['evrium_name'] }}
            @endif
          </div>
          <div class="student-sub">{{ $s['time_start'] }}{{ $s['time_end'] ? ' – ' . $s['time_end'] : '' }}
            @if($s['linked'])
              <span class="link-badge">{{ $s['evrium_name'] }}</span>
            @else
              <span class="link-badge" style="color:var(--red);">не привязан</span>
            @endif
          </div>
        </div>
        @if($s['linked'])
          <button class="assign-btn" @click="openAssign({{ $s['student_id'] }}, '{{ e($s['student_alias'] ?? $s['student_name'] ?? '') }}')">Дать ДЗ</button>
        @endif
      </div>
    @empty
      <div class="empty-note">Нет данных о прошлом уроке.</div>
    @endforelse
  </div>

  <div x-show="tab === 'all'" x-cloak>
    @forelse($allStudents as $i => $s)
      <div class="student-row" style="--i:{{ $i }}" x-data="{ editing: false }">
        <div style="flex:1;min-width:0;">
          <div class="student-name">{{ $s->student_alias ?? $s->name }}</div>
          <div class="student-sub">
            {{ $s->name }}
            @if($s->evrium_name)
              <span class="link-badge">{{ $s->evrium_name }}</span>
            @else
              <span class="link-badge" style="color:var(--red);">не привязан</span>
            @endif
          </div>

          <div class="link-form" x-show="editing" x-cloak>
            <label>Имя (алиас)</label>
            <input type="text" x-ref="alias{{ $s->id }}" value="{{ $s->student_alias ?? '' }}" placeholder="Отображаемое имя...">

            <label>Привязка к расписанию (Эвриум)</label>
            <select x-ref="evrium{{ $s->id }}">
              <option value="">— не привязан —</option>
              @foreach($allEvriumNames as $en)
                <option value="{{ $en }}" {{ $s->evrium_name === $en ? 'selected' : '' }}>{{ $en }}</option>
              @endforeach
            </select>

            <button class="link-save" @click="saveLink({{ $s->id }}, $refs.alias{{ $s->id }}.value, $refs.evrium{{ $s->id }}.value); editing = false">Сохранить</button>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
          <button class="settings-btn" @click="editing = !editing" x-text="editing ? '✕' : '⚙'"></button>
          <button class="assign-btn" @click="openAssign({{ $s->id }}, '{{ e($s->student_alias ?? $s->name) }}')">Дать ДЗ</button>
        </div>
      </div>
    @empty
      <div class="empty-note">Нет учеников.</div>
    @endforelse
  </div>

  @if($recentHomework->count() > 0)
  <div class="sec-label" style="margin-top: 16px;">Выданные ДЗ</div>
  @foreach($recentHomework as $hw)
    <div class="hw-card">
      <div class="hw-title">{{ $hw->title }}</div>
      <div class="hw-meta">{{ $hw->assigned_at?->format('d.m.Y H:i') }}</div>
      @foreach($hw->assignments as $a)
        <div style="margin-top: 4px; font-size: 12px; color: var(--text);">
          {{ $a->student?->name ?? '?' }}
          <span class="hw-status-badge {{ 'badge-' . $a->status }}">{{ $a->status }}</span>
        </div>
      @endforeach
    </div>
  @endforeach
  @endif

  <template x-if="showAssign">
    <div class="fv-overlay" @click.self="showAssign = false">
      <div class="fv-sheet">
        <div class="fv-handle"></div>
        <div class="fv-title">ДЗ для <span x-text="assignName"></span></div>

        <form method="POST" action="/homework/assign">
          @csrf
          <input type="hidden" name="student_id" :value="assignStudentId">

          <div class="fv-option" style="cursor:pointer" @click="$refs.typeFullVariant.checked = true; selectedType = 'full_variant'">
            <div class="fv-opt-icon">📝</div>
            <div>
              <div class="fv-opt-title">Полный вариант</div>
              <div class="fv-opt-desc">Автоматически создать и выдать полный вариант ОГЭ</div>
            </div>
            <input type="radio" name="type" value="full_variant" x-ref="typeFullVariant" x-model="selectedType">
          </div>

          <div class="fv-option" style="cursor:pointer" @click="$refs.typeTopic.checked = true; selectedType = 'topic_practice'">
            <div class="fv-opt-icon">🎯</div>
            <div>
              <div class="fv-opt-title">Практика по теме</div>
              <div class="fv-opt-desc">Выдать задания по конкретной теме</div>
            </div>
            <input type="radio" name="type" value="topic_practice" x-ref="typeTopic" x-model="selectedType">
          </div>

          <div class="fv-option" style="cursor:pointer" @click="$refs.typePhotoTopic.checked = true; selectedType = 'topic_photo_practice'">
            <div class="fv-opt-icon">▣</div>
            <div>
              <div class="fv-opt-title">10 задач с фото</div>
              <div class="fv-opt-desc">Ученик вводит ответ и прикрепляет фото решения</div>
            </div>
            <input type="radio" name="type" value="topic_photo_practice" x-ref="typePhotoTopic" x-model="selectedType">
          </div>

          <div x-show="selectedType === 'topic_practice' || selectedType === 'topic_photo_practice'" x-cloak style="margin-top:12px;">
            <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:6px;">Тема</label>
            <select name="topic_number" style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--text);">
              <option value="">Выберите тему</option>
              @foreach($topicOptions as $topic)
                <option value="{{ $topic['number'] }}">Тема {{ $topic['number'] }} · {{ $topic['title'] }}</option>
              @endforeach
            </select>
          </div>

          <div x-show="selectedType === 'topic_photo_practice'" x-cloak style="margin-top:12px;">
            <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:6px;">Ученики</label>
            <div class="student-pick-list">
              @foreach($allStudents as $student)
                <label class="student-pick">
                  <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" :checked="assignStudentId === {{ $student->id }}">
                  <span>{{ $student->student_alias ?? $student->name }}</span>
                </label>
              @endforeach
            </div>
          </div>

          <button type="submit" class="btn btn-accent" style="margin-top:14px; width:100%;">Выдать ДЗ</button>
        </form>
      </div>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
function teacherHw() {
  return {
    tab: 'current',
    showAssign: false,
    assignStudentId: null,
    assignName: '',
    selectedType: 'full_variant',

    openAssign(studentId, name) {
      this.assignStudentId = studentId;
      this.assignName = name;
      this.selectedType = 'full_variant';
      this.showAssign = true;
    },

    async saveLink(studentId, alias, evriumName) {
      const res = await fetch(`/students/${studentId}/link`, {
        method: 'PATCH',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window._csrf,
        },
        body: JSON.stringify({
          alias: alias || '',
          evrium_name: evriumName || '',
        }),
      });

      if (!res.ok) {
        const tg = window.Telegram?.WebApp;
        if (tg?.showAlert) tg.showAlert('Не удалось сохранить привязку');
        return;
      }

      window.location.reload();
    }
  };
}
</script>
@endpush
