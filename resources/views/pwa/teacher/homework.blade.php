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
  .profile-stack { display: flex; flex-direction: column; gap: 4px; margin-top: 7px; }
  .profile-chip {
    display: inline-flex; align-items: center; gap: 6px; width: fit-content;
    max-width: 100%; padding: 4px 7px; border-radius: 8px;
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text); font-size: 11px; font-weight: 700;
  }
  .profile-chip span { color: var(--muted); font-weight: 600; }
  .link-actions { display: flex; flex-direction: column; gap: 6px; align-items: flex-end; }
  .link-btn {
    font-size: 11px; font-weight: 800; color: var(--accent);
    background: var(--accent-bg); border: 1px solid var(--accent-bd);
    border-radius: 8px; padding: 6px 12px; cursor: pointer;
    white-space: nowrap;
  }
  .link-btn:active { opacity: .7; }

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
  .profile-search {
    width: 100%; padding: 11px 12px; border-radius: 10px;
    border: 1px solid var(--border); background: var(--surface);
    color: var(--text); font-size: 13px; margin-bottom: 10px;
  }
  .profile-mode-tabs { display: flex; gap: 6px; margin-bottom: 10px; }
  .profile-mode-tab {
    flex: 1; text-align: center; padding: 8px 6px;
    font-size: 11px; font-weight: 800; color: var(--muted);
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 8px; cursor: pointer;
  }
  .profile-mode-tab.active {
    color: var(--accent); background: var(--accent-bg);
    border-color: var(--accent-bd);
  }
  .profile-mode-sub { font-size: 9px; font-weight: 600; margin-top: 2px; opacity: 0.75; }
  .profile-list { max-height: 310px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
  .profile-option {
    width: 100%; text-align: left; border: 1px solid var(--border);
    background: var(--surface); color: var(--text); border-radius: 12px;
    padding: 10px 11px; cursor: pointer;
  }
  .profile-option:active { background: var(--surface2); }
  .profile-option-main { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
  .profile-option-name { font-size: 13px; font-weight: 800; }
  .profile-option-meta { font-size: 11px; color: var(--muted); margin-top: 2px; line-height: 1.35; }
  .profile-option-time { font-size: 11px; font-weight: 800; color: var(--accent); white-space: nowrap; }
  .profile-option-hit { color: var(--green); }
  .profile-empty { text-align: center; padding: 20px 10px; color: var(--muted); font-size: 12px; font-weight: 600; }

  .task-pick-list {
    max-height: 280px; overflow-y: auto;
    display: flex; flex-direction: column; gap: 6px;
    padding-right: 2px;
  }
  .task-pick {
    display: flex; gap: 10px; align-items: flex-start;
    padding: 9px 11px; border-radius: 10px;
    background: var(--surface); border: 1px solid var(--border);
    color: var(--text); font-size: 12px; cursor: pointer;
    line-height: 1.35;
  }
  .task-pick-active { border-color: var(--accent-bd); background: var(--accent-bg); }
  .task-pick input[type=checkbox] { margin-top: 2px; flex-shrink: 0; }
  .task-pick-body { flex: 1; min-width: 0; }
  .task-pick-head {
    display: flex; justify-content: space-between; gap: 6px;
    font-weight: 800; margin-bottom: 2px;
  }
  .task-pick-num { color: var(--text); font-size: 12px; }
  .task-pick-answer { color: var(--muted); font-size: 10px; font-weight: 700; white-space: nowrap; }
  .task-pick-instruction { color: var(--muted); font-size: 10px; font-weight: 700; margin-bottom: 2px; }
  .task-pick-text { color: var(--text); font-size: 12px; font-weight: 600; }
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
        <div class="link-actions">
          @if($s['linked'])
            <button class="assign-btn" @click="openAssignMany(@js($s['student_ids'] ?? [$s['student_id']]), @js($s['student_alias'] ?? $s['student_name'] ?? $s['evrium_name']))">Дать ДЗ</button>
            <button class="link-btn" @click="openLink(@js($s['evrium_name']), @js($s['time_start'] ?? ''), @js($s['time_end'] ?? ''), @js($s['lesson_date'] ?? ''))">+ профиль</button>
          @else
            <button class="link-btn" @click="openLink(@js($s['evrium_name']), @js($s['time_start'] ?? ''), @js($s['time_end'] ?? ''), @js($s['lesson_date'] ?? ''))">Привязать</button>
          @endif
        </div>
      </div>
      @if($s['linked'] && count($s['linked_profiles'] ?? []) > 0)
        <div class="profile-stack" style="margin:-4px 0 10px 14px;">
          @foreach($s['linked_profiles'] as $profile)
            <div class="profile-chip">
              {{ $profile['student_alias'] ?: $profile['student_name'] }}
              @if($profile['last_active_at'])
                <span>{{ $profile['last_active_at']->format('d.m H:i') }}</span>
              @endif
            </div>
          @endforeach
        </div>
      @endif
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
        <div class="link-actions">
          @if($s['linked'])
            <button class="assign-btn" @click="openAssignMany(@js($s['student_ids'] ?? [$s['student_id']]), @js($s['student_alias'] ?? $s['student_name'] ?? $s['evrium_name']))">Дать ДЗ</button>
            <button class="link-btn" @click="openLink(@js($s['evrium_name']), @js($s['time_start'] ?? ''), @js($s['time_end'] ?? ''), @js($s['lesson_date'] ?? ''))">+ профиль</button>
          @else
            <button class="link-btn" @click="openLink(@js($s['evrium_name']), @js($s['time_start'] ?? ''), @js($s['time_end'] ?? ''), @js($s['lesson_date'] ?? ''))">Привязать</button>
          @endif
        </div>
      </div>
      @if($s['linked'] && count($s['linked_profiles'] ?? []) > 0)
        <div class="profile-stack" style="margin:-4px 0 10px 14px;">
          @foreach($s['linked_profiles'] as $profile)
            <div class="profile-chip">
              {{ $profile['student_alias'] ?: $profile['student_name'] }}
              @if($profile['last_active_at'])
                <span>{{ $profile['last_active_at']->format('d.m H:i') }}</span>
              @endif
            </div>
          @endforeach
        </div>
      @endif
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

        <form method="POST" action="/homework/assign" @submit="handleAssignSubmit($event)">
          @csrf
          <input type="hidden" name="student_id" :value="assignStudentId">

          <div class="fv-option" style="cursor:pointer" @click="$refs.typeMiniVariant.checked = true; selectedType = 'mini_variant'">
            <div class="fv-opt-icon">📝</div>
            <div>
              <div class="fv-opt-title" x-text="miniVariantTitle()"></div>
              <div class="fv-opt-desc" x-text="miniVariantDesc()"></div>
            </div>
            <input type="radio" name="type" value="mini_variant" x-ref="typeMiniVariant" x-model="selectedType">
          </div>

          <div class="fv-option" style="cursor:pointer" @click="$refs.typePhotoTopic.checked = true; selectedType = 'topic_photo_practice'">
            <div class="fv-opt-icon">🎯</div>
            <div>
              <div class="fv-opt-title">Практика по теме</div>
              <div class="fv-opt-desc">Выбираешь тему и конкретные задачи. Ученик решает и прикладывает фото решения</div>
            </div>
            <input type="radio" name="type" value="topic_photo_practice" x-ref="typePhotoTopic" x-model="selectedType">
          </div>

          <div x-show="selectedType === 'topic_photo_practice'" x-cloak style="margin-top:12px;">
            <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:6px;">Тема</label>
            <select name="topic_number" x-model="selectedTopic" @change="onTopicChange()" style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--text);">
              <option value="">Выберите тему</option>
              @foreach($topicOptions as $topic)
                <option value="{{ $topic['number'] }}">Тема {{ $topic['number'] }} · {{ $topic['title'] }}</option>
              @endforeach
            </select>
          </div>

          <div x-show="selectedType === 'topic_photo_practice' && selectedTopic" x-cloak style="margin-top:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <label style="font-size:12px; color:var(--muted);">Задачи <span x-text="taskPickerLabel()"></span></label>
              <div style="display:flex; gap:6px;">
                <button type="button" class="link-btn" style="padding:4px 8px;" @click="selectAllTopicTasks()">Все</button>
                <button type="button" class="link-btn" style="padding:4px 8px;" @click="selectRandomTopicTasks(10)">Случайные 10</button>
                <button type="button" class="link-btn" style="padding:4px 8px;" @click="clearTopicTasks()">Сбросить</button>
              </div>
            </div>
            <div x-show="topicTasksLoading" x-cloak class="profile-empty">Загрузка задач…</div>
            <div x-show="!topicTasksLoading && topicTasksError" x-cloak class="profile-empty" style="color:var(--red);" x-text="topicTasksError"></div>
            <div x-show="!topicTasksLoading && !topicTasksError" class="task-pick-list" x-cloak>
              <template x-for="task in topicTasks" :key="task.index">
                <label class="task-pick" :class="selectedTaskIndices.includes(task.index) && 'task-pick-active'">
                  <input type="checkbox" name="task_indices[]" :value="task.index"
                         :checked="selectedTaskIndices.includes(task.index)"
                         @change="toggleTaskIndex(task.index)">
                  <div class="task-pick-body">
                    <div class="task-pick-head">
                      <span class="task-pick-num" x-text="'#' + task.task_order_hint"></span>
                      <span class="task-pick-answer" x-text="'ответ: ' + task.answer"></span>
                    </div>
                    <div class="task-pick-instruction" x-show="task.instruction" x-text="task.instruction"></div>
                    <div class="task-pick-text" x-text="task.text"></div>
                  </div>
                </label>
              </template>
              <div x-show="topicTasks.length === 0" class="profile-empty">В этой теме нет задач с ответами.</div>
            </div>
          </div>

          <div x-show="selectedType === 'topic_photo_practice'" x-cloak style="margin-top:12px;">
            <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:6px;">Ученики</label>
            <div class="student-pick-list">
              @foreach($allStudents as $student)
                <label class="student-pick">
                  <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" :checked="assignStudentIds.includes({{ $student->id }})">
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

  <template x-if="showLink">
    <div class="fv-overlay" @click.self="showLink = false">
      <div class="fv-sheet">
        <div class="fv-handle"></div>
        <div class="fv-title">Привязать <span x-text="linkEvriumName"></span></div>
        <div class="profile-mode-tabs" x-show="linkHasLessonWindow()" x-cloak>
          <div class="profile-mode-tab" :class="profileMode === 'all' && 'active'" @click="profileMode = 'all'">
            Все
            <div class="profile-mode-sub" x-text="profiles.length + ' уч.'"></div>
          </div>
          <div class="profile-mode-tab" :class="profileMode === 'online' && 'active'" @click="profileMode = 'online'">
            Онлайн на уроке
            <div class="profile-mode-sub" x-text="lessonWindowLabel()"></div>
          </div>
        </div>
        <input class="profile-search" type="search" x-model="profileSearch" placeholder="Поиск профиля">
        <div class="profile-list">
          <template x-for="profile in filteredProfiles()" :key="profile.id">
            <button type="button" class="profile-option" @click="linkProfile(profile)">
              <div class="profile-option-main">
                <div>
                  <div class="profile-option-name" x-text="profile.student_alias || profile.name"></div>
                  <div class="profile-option-meta">
                    <span x-text="profile.name"></span>
                    <template x-if="profile.email"><span> · <span x-text="profile.email"></span></span></template>
                  </div>
                  <div class="profile-option-meta">
                    <span x-text="profile.is_linked_to_teacher ? 'уже в ваших учениках' : 'новый профиль для вас'"></span>
                    <template x-if="profile.linked_evrium_name">
                      <span> · расписание: <span x-text="profile.linked_evrium_name"></span></span>
                    </template>
                  </div>
                </div>
                <div class="profile-option-time" :class="wasOnlineAtLesson(profile) && 'profile-option-hit'"
                     x-text="wasOnlineAtLesson(profile) ? ('на уроке · ' + profile.last_active_label) : profile.last_active_label"></div>
              </div>
            </button>
          </template>
          <template x-if="filteredProfiles().length === 0">
            <div class="profile-empty" x-text="profileMode === 'online' ? 'Никто не был онлайн во время этого урока. Попробуйте вкладку «Все».' : 'Никого не нашли. Очистите поиск или поменяйте имя.'"></div>
          </template>
        </div>
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
    showLink: false,
    assignStudentId: null,
    assignStudentIds: [],
    assignName: '',
    selectedType: 'full_variant',
    linkEvriumName: '',
    linkTimeStart: '',
    linkTimeEnd: '',
    linkLessonDate: '',
    profileSearch: '',
    profileMode: 'all',
    profiles: @json($profileLinkOptions),
    selectedTopic: '',
    topicTasks: [],
    topicTasksLoading: false,
    topicTasksError: '',
    selectedTaskIndices: [],
    studentGrades: @json($studentGrades ?? (object) []),

    assignStudentGrade() {
      if (!this.assignStudentId) return null;
      const g = this.studentGrades[this.assignStudentId];
      return typeof g === 'number' ? g : null;
    },

    isAssignVpr() {
      const g = this.assignStudentGrade();
      return g !== null && g >= 5 && g <= 8;
    },

    miniVariantTitle() {
      const g = this.assignStudentGrade();
      if (g !== null && g >= 5 && g <= 8) return `Мини-ВПР ${g} класс`;
      return 'Мини-вариант ОГЭ';
    },

    miniVariantDesc() {
      return this.isAssignVpr()
        ? 'Короткий вариант ВПР, автоматически сгенерирован под класс ученика'
        : '7 задач (4 алгебра + 3 геометрия), автоматически сгенерирован';
    },

    openAssign(studentId, name) {
      this.assignStudentId = studentId;
      this.assignStudentIds = [studentId];
      this.assignName = name;
      this.resetAssignForm();
      this.showAssign = true;
    },

    openAssignMany(studentIds, name) {
      const ids = (studentIds || []).filter(Boolean);
      this.assignStudentId = ids[0] || null;
      this.assignStudentIds = ids;
      this.assignName = name;
      this.resetAssignForm();
      this.showAssign = true;
    },

    resetAssignForm() {
      this.selectedType = 'mini_variant';
      this.selectedTopic = '';
      this.topicTasks = [];
      this.topicTasksError = '';
      this.selectedTaskIndices = [];
    },

    async onTopicChange() {
      this.selectedTaskIndices = [];
      this.topicTasks = [];
      this.topicTasksError = '';
      if (!this.selectedTopic) return;
      this.topicTasksLoading = true;
      try {
        const res = await fetch(`/homework/topic-tasks/${this.selectedTopic}`, {
          credentials: 'include',
          headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        this.topicTasks = Array.isArray(data.tasks) ? data.tasks : [];
      } catch (e) {
        this.topicTasksError = 'Не удалось загрузить задачи темы.';
      } finally {
        this.topicTasksLoading = false;
      }
    },

    toggleTaskIndex(idx) {
      const i = this.selectedTaskIndices.indexOf(idx);
      if (i >= 0) this.selectedTaskIndices.splice(i, 1);
      else this.selectedTaskIndices.push(idx);
    },

    selectAllTopicTasks() {
      this.selectedTaskIndices = this.topicTasks.map(t => t.index);
    },

    selectRandomTopicTasks(n) {
      const all = this.topicTasks.map(t => t.index);
      for (let i = all.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [all[i], all[j]] = [all[j], all[i]];
      }
      this.selectedTaskIndices = all.slice(0, Math.min(n, all.length));
    },

    clearTopicTasks() {
      this.selectedTaskIndices = [];
    },

    taskPickerLabel() {
      const total = this.topicTasks.length;
      const picked = this.selectedTaskIndices.length;
      if (total === 0) return '';
      return `(${picked} из ${total})`;
    },

    handleAssignSubmit(event) {
      if (this.selectedType === 'topic_photo_practice') {
        if (!this.selectedTopic) {
          event.preventDefault();
          alert('Выберите тему.');
          return;
        }
        if (this.selectedTaskIndices.length === 0) {
          event.preventDefault();
          alert('Выберите хотя бы одну задачу.');
          return;
        }
      }
    },

    openLink(evriumName, timeStart, timeEnd, lessonDate) {
      this.linkEvriumName = evriumName || '';
      this.linkTimeStart = timeStart || '';
      this.linkTimeEnd = timeEnd || '';
      this.linkLessonDate = lessonDate || '';
      this.profileSearch = '';
      this.profileMode = this.linkHasLessonWindow() ? 'online' : 'all';
      this.showLink = true;
    },

    linkHasLessonWindow() {
      return !!(this.linkLessonDate && this.linkTimeStart);
    },

    lessonWindowLabel() {
      if (!this.linkTimeStart) return '—';
      const end = this.linkTimeEnd || '';
      return end ? (this.linkTimeStart + '–' + end) : this.linkTimeStart;
    },

    parseLessonDateTime(time) {
      if (!this.linkLessonDate || !time) return null;
      const parts = String(time).split(':');
      if (parts.length < 2) return null;
      const [y, mo, d] = this.linkLessonDate.split('-').map(Number);
      const h = parseInt(parts[0], 10);
      const mi = parseInt(parts[1], 10);
      if ([y, mo, d, h, mi].some(Number.isNaN)) return null;
      return new Date(y, mo - 1, d, h, mi, 0, 0).getTime();
    },

    wasOnlineAtLesson(profile) {
      if (!this.linkHasLessonWindow() || !profile.last_active_at) return false;
      const start = this.parseLessonDateTime(this.linkTimeStart);
      const end = this.linkTimeEnd ? this.parseLessonDateTime(this.linkTimeEnd) : (start !== null ? start + 60 * 60 * 1000 : null);
      if (start === null || end === null) return false;
      const pad = 10 * 60 * 1000;
      const ts = Date.parse(profile.last_active_at);
      if (Number.isNaN(ts)) return false;
      return ts >= (start - pad) && ts <= (end + pad);
    },

    filteredProfiles() {
      const q = (this.profileSearch || '').trim().toLowerCase();
      const terms = q.split(/\s+/).filter(Boolean);
      const onlyOnline = this.profileMode === 'online' && this.linkHasLessonWindow();
      return this.profiles.filter((profile) => {
        if (onlyOnline && !this.wasOnlineAtLesson(profile)) return false;
        if (!terms.length) return true;
        const haystack = [
          profile.name || '',
          profile.email || '',
          profile.student_alias || '',
          profile.linked_evrium_name || '',
        ].join(' ').toLowerCase();
        return terms.every((term) => haystack.includes(term));
      }).slice(0, 120);
    },

    async linkProfile(profile) {
      const res = await fetch(`/students/${profile.id}/link`, {
        method: 'PATCH',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window._csrf,
        },
        body: JSON.stringify({
          alias: profile.student_alias || profile.name || '',
          evrium_name: this.linkEvriumName || '',
        }),
      });

      if (!res.ok) {
        const tg = window.Telegram?.WebApp;
        if (tg?.showAlert) tg.showAlert('Не удалось привязать профиль');
        return;
      }

      window.location.reload();
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
