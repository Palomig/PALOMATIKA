@extends('layouts.miniapp')
@section('title', 'Домашка — palomatika')

@push('styles')
  .homework-layout { display:flex; flex-direction:column; gap:16px; }
  .segment {
    display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px;
    padding:6px; border-radius:20px; background:var(--surface); border:1px solid var(--border);
    box-shadow:0 12px 34px rgba(0,0,0,.2);
  }
  .segment button {
    min-height:42px; border:none; border-radius:16px; background:transparent; color:var(--muted); font-size:13px; font-weight:900;
  }
  .segment button.active { background:var(--accent-bg); color:var(--accent); }
  .assign-grid { display:flex; flex-direction:column; gap:10px; }
  .assign-card {
    padding:15px; border-radius:20px; background:var(--surface);
    border:1px solid var(--border); box-shadow:0 12px 34px rgba(0,0,0,.2);
  }
  .assign-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
  .assign-card-title { font-size:15px; font-weight:900; color:var(--text); }
  .assign-card-subtitle { margin-top:4px; font-size:12px; color:var(--muted); }
  .assign-card-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
  .icon-btn {
    display:inline-flex; align-items:center; justify-content:center;
    min-height:38px; padding:0 14px; border-radius:14px; border:none; text-decoration:none;
    background:var(--accent); color:#fff; font-size:12px; font-weight:800;
  }
  .icon-btn.secondary {
    background:var(--surface2); color:var(--text); border:1px solid var(--border);
  }
  .control-list { display:flex; flex-direction:column; gap:10px; }
  .control-card {
    padding:16px; border-radius:20px; background:var(--surface);
    border:1px solid var(--border); box-shadow:0 12px 34px rgba(0,0,0,.2);
  }
  .control-card-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
  .control-students { display:flex; flex-direction:column; gap:8px; margin-top:12px; }
  .control-student-row {
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    padding:10px 12px; border-radius:14px; background:var(--surface2);
  }
  .inline-settings { margin-top:12px; padding-top:12px; border-top:1px solid var(--border); display:flex; flex-direction:column; gap:10px; }
  .inline-settings input, .inline-settings select {
    width:100%; min-height:42px; padding:0 12px; border-radius:14px;
    border:1px solid var(--border); background:var(--surface2); color:var(--text); font-size:13px; font-weight:700;
  }
  .sheet-backdrop {
    position:fixed; inset:0; background:rgba(9,20,38,.38); z-index:140;
    display:flex; align-items:flex-end; justify-content:center; padding:16px;
  }
  .sheet {
    width:min(480px,100%); border-radius:28px 28px 18px 18px; background:var(--bg); color:var(--text);
    border:1px solid var(--border);
    padding:14px 16px calc(22px + var(--safe-bottom)); box-shadow:0 18px 50px rgba(9,20,38,.28);
  }
  .sheet-handle { width:52px; height:5px; border-radius:999px; background:#d7dfeb; margin:0 auto 12px; }
  .sheet-title { font-size:20px; font-weight:900; color:var(--text); }
  .sheet-subtitle { margin-top:6px; font-size:13px; color:var(--muted); }
  .sheet-options { display:flex; flex-direction:column; gap:10px; margin-top:16px; }
  .sheet-option {
    display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px;
    border-radius:18px; border:1px solid var(--border); background:var(--surface);
  }
  .sheet-option strong { display:block; font-size:14px; color:var(--text); }
  .sheet-option span { display:block; margin-top:4px; font-size:12px; color:var(--muted); }
  .sheet-field { margin-top:14px; }
  .sheet-field label { display:block; margin-bottom:6px; font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
  .sheet-field select { width:100%; min-height:44px; border-radius:14px; border:1px solid var(--border); background:var(--surface2); color:var(--text); padding:0 12px; font-size:13px; font-weight:700; }
  .sheet-footer { display:flex; gap:8px; margin-top:16px; }
  .sheet-footer button { flex:1; min-height:44px; border-radius:16px; font-size:13px; font-weight:900; }
  .sheet-footer .cancel { border:1px solid var(--border); background:var(--surface2); color:var(--text); }
  .sheet-footer .submit { border:none; background:var(--accent); color:#fff; }
@endpush

@section('body')
<div class="page page--teacher" x-data="teacherHw()">
  <div class="homework-layout">
    <section class="mini-hero">
      <div class="hero-kicker">Домашка · {{ $todayLabel }}</div>
      <div class="hero-title">Назначить быстро, контролировать спокойно</div>
      <div class="hero-subtitle">Первый сценарий для телефона: видишь текущий урок и сразу даёшь нужную практику. Второй сценарий: открываешь журнал назначений и следишь за статусами.</div>
    </section>

    @if(session('success'))
      <div class="note" style="border-left-color:#22b468;color:#0f7b45;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="note" style="border-left-color:#e04848;color:#b42318;">{{ session('error') }}</div>
    @endif

    <div class="segment">
      <button type="button" :class="mode === 'assign' && 'active'" @click="mode = 'assign'">Назначить</button>
      <button type="button" :class="mode === 'control' && 'active'" @click="mode = 'control'">Контроль</button>
    </div>

    <section class="section-card" x-show="mode === 'assign'" x-cloak>
      <div class="section-head">
        <div>
          <div class="section-title">Текущий урок</div>
          <div class="section-note">Ученики из расписания. Самый быстрый путь к выдаче ДЗ.</div>
        </div>
      </div>
      <div class="assign-grid">
        @forelse($currentStudents as $student)
          <article class="assign-card">
            <div class="assign-card-head">
              <div>
                <div class="assign-card-title">{{ $student['student_alias'] ?? $student['student_name'] ?? $student['evrium_name'] }}</div>
                <div class="assign-card-subtitle">
                  {{ $student['time_start'] ?: 'Сегодня' }}{{ $student['time_end'] ? ' - ' . $student['time_end'] : '' }}
                  · {{ $student['linked'] ? ($student['evrium_name'] ?: 'привязан') : 'не привязан' }}
                </div>
              </div>
              <span class="status-tag {{ $student['linked'] ? 'green' : 'red' }}">{{ $student['linked'] ? 'Готов' : 'Связать' }}</span>
            </div>
            <div class="assign-card-actions">
              @if($student['linked'])
                <button class="icon-btn" type="button" @click="openAssign({{ $student['student_id'] }}, @js($student['student_alias'] ?? $student['student_name'] ?? ''))">Дать ДЗ</button>
                <a class="icon-btn secondary" href="/tg/teacher/students/{{ $student['student_id'] }}">Профиль</a>
              @else
                <a class="icon-btn secondary" href="/tg/teacher/students?filter=unlinked">Привязать</a>
              @endif
            </div>
          </article>
        @empty
          <div class="note">Сегодня в расписании нет связанных уроков. Ниже остаётся общий список учеников для ручного назначения.</div>
        @endforelse
      </div>
    </section>

    <section class="section-card" x-show="mode === 'assign'" x-cloak>
      <div class="section-head">
        <div>
          <div class="section-title">Все ученики</div>
          <div class="section-note">Ручная выдача и настройка привязки к расписанию без ухода в отдельный старый экран.</div>
        </div>
        <a href="/tg/teacher/students" class="ghost-link">К профилям</a>
      </div>
      <div class="assign-grid">
        @forelse($allStudents as $student)
          <article class="assign-card" x-data="{ editing: false }">
            <div class="assign-card-head">
              <div>
                <div class="assign-card-title">{{ $student->student_alias ?? $student->name }}</div>
                <div class="assign-card-subtitle">
                  {{ $student->name }}
                  · {{ $student->evrium_name ?: 'не привязан к Evrium' }}
                </div>
              </div>
              <span class="status-tag {{ $student->evrium_name ? 'green' : 'red' }}">{{ $student->evrium_name ? 'Связан' : 'Без связи' }}</span>
            </div>
            <div class="assign-card-actions">
              <button class="icon-btn" type="button" @click="openAssign({{ $student->id }}, @js($student->student_alias ?? $student->name))">Дать ДЗ</button>
              <button class="icon-btn secondary" type="button" @click="editing = !editing" x-text="editing ? 'Скрыть настройки' : 'Настроить'"></button>
            </div>

            <div class="inline-settings" x-show="editing" x-cloak>
              <input type="text" x-ref="alias{{ $student->id }}" value="{{ $student->student_alias ?? '' }}" placeholder="Алиас">
              <select x-ref="evrium{{ $student->id }}">
                <option value="">— не привязан —</option>
                @foreach($allEvriumNames as $name)
                  <option value="{{ $name }}" {{ $student->evrium_name === $name ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
              </select>
              <div class="assign-card-actions" style="margin-top:0;">
                <button class="icon-btn secondary" type="button" @click="editing = false">Отмена</button>
                <button class="icon-btn" type="button" @click="saveLink({{ $student->id }}, $refs.alias{{ $student->id }}.value, $refs.evrium{{ $student->id }}.value)">Сохранить</button>
              </div>
            </div>
          </article>
        @empty
          <div class="note">Пока нет учеников для назначения.</div>
        @endforelse
      </div>
    </section>

    <section class="section-card" x-show="mode === 'control'" x-cloak>
      <div class="section-head">
        <div>
          <div class="section-title">Контроль выполнения</div>
          <div class="section-note">Последние назначенные задания и статус по каждому ученику.</div>
        </div>
      </div>
      <div class="control-list">
        @forelse($recentHomework as $hw)
          <article class="control-card">
            <div class="control-card-top">
              <div>
                <div class="assign-card-title">{{ $hw->title }}</div>
                <div class="assign-card-subtitle">{{ $hw->assigned_at?->format('d.m.Y H:i') }}</div>
              </div>
              <span class="status-tag accent">{{ $hw->assignments->count() }} уч.</span>
            </div>
            <div class="control-students">
              @foreach($hw->assignments as $assignment)
                <div class="control-student-row">
                  <div>
                    <strong style="display:block;color:var(--text);font-size:13px;">{{ $assignment->student?->name ?? 'Ученик' }}</strong>
                    <span style="display:block;margin-top:4px;font-size:12px;color:var(--muted);">{{ $assignment->status }}</span>
                  </div>
                  <span class="status-tag {{ $assignment->status === 'completed' ? 'green' : ($assignment->status === 'started' ? 'yellow' : 'accent') }}">{{ $assignment->status }}</span>
                </div>
              @endforeach
            </div>
          </article>
        @empty
          <div class="note">Пока нет выданных домашних заданий.</div>
        @endforelse
      </div>
    </section>
  </div>

  <template x-if="showAssign">
    <div class="sheet-backdrop" @click.self="showAssign = false">
      <div class="sheet">
        <div class="sheet-handle"></div>
        <div class="sheet-title">Дать ДЗ</div>
        <div class="sheet-subtitle">Ученик: <strong x-text="assignName"></strong></div>

        <form method="POST" action="/tg/teacher/homework/assign">
          @csrf
          <input type="hidden" name="student_id" :value="assignStudentId">
          <input type="hidden" name="type" :value="selectedType">

          <div class="sheet-options">
            <button type="button" class="sheet-option" @click="selectedType = 'full_variant'">
              <div>
                <strong>Полный вариант</strong>
                <span>Собрать полноценную домашку по ОГЭ.</span>
              </div>
              <span class="status-tag" :class="selectedType === 'full_variant' ? 'accent' : 'green'" x-text="selectedType === 'full_variant' ? 'Выбрано' : 'Выбрать'"></span>
            </button>

            <button type="button" class="sheet-option" @click="selectedType = 'topic_practice'">
              <div>
                <strong>Конкретная тема</strong>
                <span>Прицельная практика по слабому месту ученика.</span>
              </div>
              <span class="status-tag" :class="selectedType === 'topic_practice' ? 'accent' : 'green'" x-text="selectedType === 'topic_practice' ? 'Выбрано' : 'Выбрать'"></span>
            </button>
          </div>

          <div class="sheet-field" x-show="selectedType === 'topic_practice'" x-cloak>
            <label>Номер задания</label>
            <select name="topic_number">
              @foreach([6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,23] as $number)
                <option value="{{ $number }}">{{ $number }}</option>
              @endforeach
            </select>
          </div>

          <div class="sheet-footer">
            <button class="cancel" type="button" @click="showAssign = false">Отмена</button>
            <button class="submit" type="submit">Назначить</button>
          </div>
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
    mode: 'assign',
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

    async saveLink(studentId, alias, evrium_name) {
      const res = await fetch(`/tg/teacher/students/${studentId}/link`, {
        method: 'PATCH',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window._csrf,
        },
        body: JSON.stringify({ alias: alias.trim(), evrium_name: evrium_name.trim() }),
      });

      const tg = window.Telegram?.WebApp;
      if (!res.ok) {
        if (tg?.showAlert) tg.showAlert('Не удалось сохранить привязку');
        return;
      }

      if (tg?.showAlert) tg.showAlert('Привязка сохранена');
      window.location.reload();
    }
  };
}
</script>
@endpush
