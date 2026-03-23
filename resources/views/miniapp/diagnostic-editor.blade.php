@extends('layouts.miniapp')
@section('title', 'Редактор диагностики')

@push('styles')
  /* Layout */
  .de-wrap { display: flex; gap: 0; height: calc(100vh - 56px); overflow: hidden; }

  /* Left panel — skills */
  .de-left {
    width: 260px; flex-shrink: 0;
    border-right: 1px solid var(--border);
    overflow-y: auto; background: var(--surface);
  }
  .de-left-title {
    padding: 14px 14px 10px;
    font-size: 11px; font-weight: 800; color: var(--muted);
    text-transform: uppercase; letter-spacing: 0.1em;
    border-bottom: 1px solid var(--border);
    position: sticky; top: 0; background: var(--surface); z-index: 2;
  }
  .cat-group { border-bottom: 1px solid var(--border); }
  .cat-name {
    padding: 8px 14px 6px;
    font-size: 11px; font-weight: 800; color: var(--accent);
    text-transform: uppercase; letter-spacing: 0.08em;
  }
  .skill-btn {
    display: flex; align-items: center; justify-content: space-between;
    width: 100%; padding: 7px 14px;
    background: none; border: none; text-align: left; cursor: pointer;
    font-size: 13px; color: var(--text);
    transition: background 0.1s;
  }
  .skill-btn:hover { background: var(--surface2); }
  .skill-btn.active { background: rgba(79,142,247,0.12); color: var(--accent); }
  .skill-count {
    font-size: 11px; font-weight: 700;
    background: var(--accent); color: #fff;
    border-radius: 10px; padding: 1px 6px; min-width: 18px; text-align: center;
  }
  .skill-count.zero { background: var(--border); color: var(--muted); }

  /* Right panel — tasks */
  .de-right { flex: 1; overflow-y: auto; padding: 0; display: flex; flex-direction: column; }

  /* Topbar */
  .de-topbar {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; border-bottom: 1px solid var(--border);
    background: var(--surface); position: sticky; top: 0; z-index: 2;
    flex-wrap: wrap;
  }
  .de-topbar-title { font-family: var(--display); font-size: 16px; color: var(--text); flex: 1; }
  .de-stat { font-size: 12px; color: var(--muted); }
  .btn-save {
    padding: 8px 16px; background: var(--accent); color: #fff;
    border: none; border-radius: 10px; font-size: 14px; font-weight: 700;
    cursor: pointer; transition: opacity 0.15s;
  }
  .btn-save:disabled { opacity: 0.5; cursor: default; }

  /* Topic selector */
  .topic-bar {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    padding: 10px 16px; border-bottom: 1px solid var(--border);
    background: var(--surface2);
  }
  .topic-label { font-size: 12px; font-weight: 700; color: var(--muted); }
  .topic-select {
    padding: 6px 10px; background: var(--bg); border: 1px solid var(--border);
    border-radius: 8px; color: var(--text); font-size: 13px; outline: none;
  }
  .btn-load {
    padding: 6px 12px; background: var(--surface); border: 1px solid var(--border);
    border-radius: 8px; color: var(--text); font-size: 13px; cursor: pointer;
  }
  .btn-load:hover { border-color: var(--accent); color: var(--accent); }

  /* Task cards */
  .task-list { padding: 12px 16px; flex: 1; }
  .task-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 13px 15px; margin-bottom: 8px;
    display: flex; align-items: flex-start; gap: 12px;
  }
  .task-card.selected { border-color: var(--accent); background: rgba(79,142,247,0.06); }
  .task-meta { font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 5px; }
  .task-text { font-size: 13px; color: var(--text); line-height: 1.5; flex: 1; }
  .task-answer { font-size: 11px; color: var(--green); margin-top: 4px; font-weight: 700; }
  .task-actions { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }
  .btn-add, .btn-remove {
    padding: 5px 10px; border-radius: 8px; font-size: 12px; font-weight: 700;
    border: none; cursor: pointer;
  }
  .btn-add { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
  .btn-remove { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-bd); }

  /* Selected tasks panel */
  .selected-panel {
    border-top: 1px solid var(--border); background: var(--surface2);
    padding: 12px 16px; max-height: 220px; overflow-y: auto;
  }
  .selected-title { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 8px; }
  .selected-item {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; color: var(--text); padding: 5px 0;
    border-bottom: 1px solid var(--border);
  }
  .selected-item:last-child { border-bottom: none; }
  .selected-skill { color: var(--accent); font-weight: 700; min-width: 100px; }
  .selected-text { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .btn-del { background: none; border: none; color: var(--red); cursor: pointer; font-size: 14px; padding: 0 4px; }

  /* Empty state */
  .empty-state { padding: 40px; text-align: center; color: var(--muted); font-size: 14px; }

  /* Toast */
  .toast {
    position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
    background: var(--green); color: #fff; padding: 10px 20px; border-radius: 12px;
    font-size: 14px; font-weight: 700; z-index: 999;
    opacity: 0; transition: opacity 0.2s; pointer-events: none;
  }
  .toast.show { opacity: 1; }
@endpush

@section('content')
<div x-data="diagEditor()" x-init="init()" style="margin: -16px;">

<div class="de-topbar">
  <div class="de-topbar-title">Редактор диагностики</div>
  <div class="de-stat" x-text="selectedItems.length + ' заданий'"></div>
  <button class="btn-save" @click="saveTest()" :disabled="saving || selectedItems.length === 0">
    <span x-show="!saving">💾 Сохранить тест</span>
    <span x-show="saving">Сохранение...</span>
  </button>
</div>

<div class="de-wrap">

  {{-- LEFT: skills list --}}
  <div class="de-left">
    <div class="de-left-title">Навыки</div>
    @foreach($skillsByCategory as $category => $skills)
    <div class="cat-group">
      <div class="cat-name">{{ $category }}</div>
      @foreach($skills as $skill)
      <button
        class="skill-btn"
        :class="{ active: activeSkillId === {{ $skill->id }} }"
        @click="selectSkill({{ $skill->id }}, {{ json_encode($skill->name) }})"
      >
        <span>{{ $skill->name }}</span>
        <span class="skill-count" :class="{ zero: countForSkill({{ $skill->id }}) === 0 }" x-text="countForSkill({{ $skill->id }})"></span>
      </button>
      @endforeach
    </div>
    @endforeach
  </div>

  {{-- RIGHT: task browser --}}
  <div class="de-right">

    {{-- Topic selector --}}
    <div class="topic-bar" x-show="activeSkillId !== null">
      <span class="topic-label">Тема ОГЭ:</span>
      <select class="topic-select" x-model="selectedTopic">
        <option value="">— выберите —</option>
        @foreach(['06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','23'] as $t)
        <option value="{{ $t }}">Тема {{ (int)$t }}</option>
        @endforeach
      </select>
      <button class="btn-load" @click="loadTasks()" :disabled="!selectedTopic || loadingTasks">
        <span x-show="!loadingTasks">Загрузить</span>
        <span x-show="loadingTasks">...</span>
      </button>
    </div>

    {{-- No skill selected --}}
    <div class="empty-state" x-show="activeSkillId === null">
      ← Выберите навык из списка слева
    </div>

    {{-- Task cards --}}
    <div class="task-list" x-show="activeSkillId !== null">
      <template x-if="browsedTasks.length === 0 && !loadingTasks">
        <div class="empty-state">Выберите тему ОГЭ и нажмите «Загрузить»</div>
      </template>
      <template x-for="(task, i) in browsedTasks" :key="i">
        <div class="task-card" :class="{ selected: isSelected(task) }">
          <div style="flex:1">
            <div class="task-meta" x-text="'Блок ' + task.block_number + ' · Задание ' + task.zadanie_number + ' · #' + task.task_index"></div>
            <div class="task-text" x-text="task.text || '(текст не задан)'"></div>
            <div class="task-answer" x-text="'Ответ: ' + task.answer"></div>
          </div>
          <div class="task-actions">
            <button class="btn-add" x-show="!isSelected(task)" @click="addTask(task)">+ Добавить</button>
            <button class="btn-remove" x-show="isSelected(task)" @click="removeTask(task)">✕ Убрать</button>
          </div>
        </div>
      </template>
    </div>

    {{-- Selected panel --}}
    <div class="selected-panel" x-show="selectedItems.length > 0">
      <div class="selected-title">Выбранные задания (<span x-text="selectedItems.length"></span>)</div>
      <template x-for="(item, i) in selectedItems" :key="i">
        <div class="selected-item">
          <span class="selected-skill" x-text="item.skill_name"></span>
          <span class="selected-text" x-text="'Тема ' + item.topic_id + ' · ' + (item.text || '')"></span>
          <button class="btn-del" @click="selectedItems.splice(i, 1)">✕</button>
        </div>
      </template>
    </div>

  </div>
</div>

<div class="toast" :class="{ show: toastVisible }" x-text="toastMsg"></div>

</div>

<script>
function diagEditor() {
  return {
    activeSkillId: null,
    activeSkillName: '',
    selectedTopic: '',
    browsedTasks: [],
    loadingTasks: false,
    saving: false,
    toastVisible: false,
    toastMsg: '',

    // Flat list of selected {skill_id, skill_name, topic_id, block_number, zadanie_number, task_index, text, answer}
    selectedItems: @json(
      collect($savedTest)->flatMap(fn($s) =>
        collect($s['tasks'] ?? [])->map(fn($t) => [
          'skill_id' => $s['skill_id'],
          'skill_name' => $s['skill_name'],
          'topic_id' => $t['topic_id'] ?? '',
          'block_number' => $t['block_number'] ?? null,
          'zadanie_number' => $t['zadanie_number'] ?? null,
          'task_index' => $t['task_index'] ?? 0,
          'text' => $t['question'] ?? $t['text'] ?? '',
          'answer' => $t['answer'] ?? '',
        ])
      )->values()
    ),

    init() {},

    selectSkill(id, name) {
      this.activeSkillId = id;
      this.activeSkillName = name;
      this.browsedTasks = [];
      this.selectedTopic = '';
    },

    countForSkill(skillId) {
      return this.selectedItems.filter(i => i.skill_id === skillId).length;
    },

    async loadTasks() {
      if (!this.selectedTopic) return;
      this.loadingTasks = true;
      this.browsedTasks = [];
      try {
        const r = await fetch(`{{ route('miniapp.teacher.diagnostic.editor.tasks') }}?topic_id=${this.selectedTopic}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await r.json();
        this.browsedTasks = data.tasks ?? [];
      } finally {
        this.loadingTasks = false;
      }
    },

    isSelected(task) {
      return this.selectedItems.some(i =>
        i.topic_id == this.selectedTopic &&
        i.block_number == task.block_number &&
        i.zadanie_number == task.zadanie_number &&
        i.task_index == task.task_index
      );
    },

    addTask(task) {
      if (this.isSelected(task)) return;
      this.selectedItems.push({
        skill_id: this.activeSkillId,
        skill_name: this.activeSkillName,
        topic_id: this.selectedTopic,
        block_number: task.block_number,
        zadanie_number: task.zadanie_number,
        task_index: task.task_index,
        text: task.text,
        answer: task.answer,
      });
    },

    removeTask(task) {
      this.selectedItems = this.selectedItems.filter(i =>
        !(i.topic_id == this.selectedTopic &&
          i.block_number == task.block_number &&
          i.zadanie_number == task.zadanie_number &&
          i.task_index == task.task_index)
      );
    },

    async saveTest() {
      this.saving = true;
      try {
        const r = await fetch('{{ route('miniapp.teacher.diagnostic.editor.save') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
          },
          body: JSON.stringify({ items: this.selectedItems }),
        });
        const data = await r.json();
        if (data.ok) {
          this.showToast(`✅ Сохранено: ${data.total_questions} заданий по ${data.total_skills} навыкам`);
        }
      } finally {
        this.saving = false;
      }
    },

    showToast(msg) {
      this.toastMsg = msg;
      this.toastVisible = true;
      setTimeout(() => { this.toastVisible = false; }, 3000);
    },
  };
}
</script>
@endsection
