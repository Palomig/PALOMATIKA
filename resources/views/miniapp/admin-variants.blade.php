@extends('layouts.miniapp')
@section('title', 'Управление вариантами — palomatika')

@push('styles')
  .admin-page { padding-top: 20px; padding-bottom: 40px; }

  /* TABS */
  .admin-tabs { display: flex; gap: 4px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 4px; margin-bottom: 16px; }
  .admin-tab {
    flex: 1; padding: 10px; border: none; border-radius: 10px;
    font-family: var(--body); font-size: 13px; font-weight: 700;
    color: var(--muted); background: transparent;
    cursor: pointer; transition: all 0.15s; text-align: center;
  }
  .admin-tab.active { background: var(--accent); color: #fff; }

  /* VARIANT LIST */
  .variant-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
  .variant-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 14px 16px;
    display: flex; align-items: center; justify-content: space-between;
    cursor: pointer; transition: background 0.15s; text-decoration: none; color: inherit;
  }
  .variant-card:active { background: var(--surface2); }
  .vc-info { flex: 1; min-width: 0; }
  .vc-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .vc-meta { font-size: 11px; font-weight: 600; color: var(--muted); display: flex; gap: 10px; }
  .vc-actions { display: flex; gap: 6px; flex-shrink: 0; }
  .vc-btn {
    width: 32px; height: 32px; border: 1px solid var(--border); border-radius: 8px;
    background: var(--surface); color: var(--muted); font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background 0.15s;
  }
  .vc-btn:active { background: var(--surface2); }

  /* CREATE FORM */
  .create-section { display: flex; flex-direction: column; gap: 14px; }
  .field-label { font-size: 12px; font-weight: 800; color: var(--text); letter-spacing: 0.03em; margin-bottom: 6px; }
  .field-input {
    width: 100%; background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 14px 16px;
    font-family: var(--body); font-size: 15px; font-weight: 700; color: var(--text);
    outline: none; transition: border-color 0.2s;
  }
  .field-input::placeholder { color: var(--muted2); font-weight: 600; }
  .field-input:focus { border-color: var(--accent); }

  /* TOPIC PICKER */
  .topic-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin-bottom: 12px; }
  .topic-btn {
    aspect-ratio: 1; background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 10px; font-family: var(--display); font-size: 14px;
    color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.15s; user-select: none;
  }
  .topic-btn.selected { border-color: var(--accent); color: var(--accent); background: var(--accent-bg); }
  .topic-btn:active { background: var(--surface2); }

  /* TASK SELECTOR FOR SELECTED TOPIC */
  .task-selector { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 14px; margin-bottom: 8px; }
  .ts-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
  .ts-title { font-size: 13px; font-weight: 800; color: var(--text); }
  .ts-subtitle { font-size: 11px; font-weight: 600; color: var(--muted); }

  .task-list { display: flex; flex-direction: column; gap: 4px; max-height: 200px; overflow-y: auto; }
  .task-row {
    display: flex; align-items: center; gap: 10px; padding: 8px 10px;
    border-radius: 8px; cursor: pointer; transition: background 0.1s;
  }
  .task-row:active { background: var(--surface2); }
  .task-radio {
    width: 18px; height: 18px; border: 2px solid var(--border);
    border-radius: 50%; flex-shrink: 0; transition: all 0.15s;
    display: flex; align-items: center; justify-content: center;
  }
  .task-radio.selected { border-color: var(--accent); background: var(--accent); }
  .task-radio.selected::after { content: ''; width: 6px; height: 6px; background: #fff; border-radius: 50%; }
  .task-preview { font-size: 12px; font-weight: 600; color: var(--text); flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

  /* SELECTED TASKS SUMMARY */
  .selected-tasks { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
  .selected-chip {
    background: var(--accent-bg); border: 1px solid var(--accent-bd); border-radius: 8px;
    padding: 5px 10px; font-size: 11px; font-weight: 700; color: var(--accent);
    display: flex; align-items: center; gap: 6px;
  }
  .chip-remove { cursor: pointer; font-size: 14px; opacity: 0.7; }
  .chip-remove:hover { opacity: 1; }

  .empty-state {
    text-align: center; padding: 40px 20px;
    font-size: 13px; font-weight: 600; color: var(--muted);
  }
  .empty-icon { font-size: 40px; margin-bottom: 10px; }
@endpush

@section('body')
<div class="page admin-page" x-data="adminVariants()">

  <div class="topbar">
    <a href="/tg/dashboard" class="back-btn">‹</a>
    <div class="topbar-title">Варианты</div>
  </div>

  {{-- TABS --}}
  <div class="admin-tabs">
    <button class="admin-tab" :class="{ active: tab === 'list' }" @click="tab = 'list'">Мои варианты</button>
    <button class="admin-tab" :class="{ active: tab === 'create' }" @click="tab = 'create'">Создать</button>
  </div>

  {{-- LIST TAB --}}
  <template x-if="tab === 'list'">
    <div>
      <template x-if="variants.length === 0">
        <div class="empty-state">
          <div class="empty-icon">📋</div>
          <div>Пока нет вариантов.<br>Создайте первый!</div>
        </div>
      </template>
      <div class="variant-list">
        <template x-for="v in variants" :key="v.id">
          <div class="variant-card">
            <div class="vc-info">
              <div class="vc-title" x-text="v.title"></div>
              <div class="vc-meta">
                <span x-text="v.task_count + ' заданий'"></span>
                <span x-text="v.created"></span>
              </div>
            </div>
            <div class="vc-actions">
              <a :href="'/tg/test-preview/' + v.hash" class="vc-btn" title="Просмотр">👁</a>
              <button class="vc-btn" @click="copyLink(v.hash)" title="Скопировать ссылку">🔗</button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </template>

  {{-- CREATE TAB --}}
  <template x-if="tab === 'create'">
    <div class="create-section">

      <div>
        <div class="field-label">Название варианта</div>
        <input class="field-input" x-model="title" placeholder="Вариант для 9А">
      </div>

      <div>
        <div class="field-label">Выберите задания по темам</div>
        <div class="sec-label" style="margin-bottom:8px;">Нажмите на номер задания, затем выберите конкретную задачу</div>

        <div class="topic-grid">
          <template x-for="t in topics" :key="t.id">
            <button class="topic-btn" :class="{ selected: activeTopic === t.id }" @click="selectTopic(t.id)" x-text="t.num"></button>
          </template>
        </div>
      </div>

      {{-- TASK SELECTOR --}}
      <template x-if="activeTopic && topicTasks.length > 0">
        <div class="task-selector">
          <div class="ts-header">
            <div class="ts-title" x-text="'Задание ' + activeTopicNum"></div>
            <div class="ts-subtitle" x-text="topicTasks.length + ' задач'"></div>
          </div>
          <div class="task-list">
            <template x-for="(task, idx) in topicTasks" :key="idx">
              <div class="task-row" @click="toggleTask(activeTopic, idx, task)">
                <div class="task-radio" :class="{ selected: isTaskSelected(activeTopic, idx) }"></div>
                <div class="task-preview" x-text="task.preview"></div>
              </div>
            </template>
          </div>
        </div>
      </template>

      {{-- SELECTED TASKS SUMMARY --}}
      <template x-if="Object.keys(selectedTasks).length > 0">
        <div>
          <div class="sec-label" style="margin-bottom:8px;">Выбрано заданий: <span x-text="Object.keys(selectedTasks).length"></span></div>
          <div class="selected-tasks">
            <template x-for="(sel, topicId) in selectedTasks" :key="topicId">
              <div class="selected-chip">
                <span x-text="'Зад. ' + topicId.replace(/^0/, '') + ': #' + (sel.index + 1)"></span>
                <span class="chip-remove" @click="removeTask(topicId)">×</span>
              </div>
            </template>
          </div>
        </div>
      </template>

      <button class="btn btn-accent" :disabled="!canCreate || creating" @click="createVariant()"
        x-text="creating ? 'Создаю...' : 'Создать вариант (' + Object.keys(selectedTasks).length + ' заданий)'">
      </button>

      <div class="note">
        💡 Выберите по одному заданию для каждого номера. Минимум 1 задание для создания варианта.
      </div>
    </div>
  </template>

</div>
@endsection

@push('scripts')
<script>
function adminVariants() {
  return {
    tab: 'list',
    variants: @json($variants ?? []),
    title: '',
    activeTopic: null,
    activeTopicNum: '',
    topicTasks: [],
    selectedTasks: {},
    creating: false,

    topics: [
      { id: '06', num: 6 }, { id: '07', num: 7 }, { id: '08', num: 8 },
      { id: '09', num: 9 }, { id: '10', num: 10 }, { id: '11', num: 11 },
      { id: '12', num: 12 }, { id: '13', num: 13 }, { id: '14', num: 14 },
      { id: '15', num: 15 }, { id: '16', num: 16 }, { id: '17', num: 17 },
      { id: '18', num: 18 }, { id: '19', num: 19 },
    ],

    get canCreate() {
      return Object.keys(this.selectedTasks).length >= 1 && this.title.trim().length >= 2;
    },

    async selectTopic(topicId) {
      this.activeTopic = topicId;
      this.activeTopicNum = parseInt(topicId, 10);
      this.topicTasks = [];

      try {
        const res = await fetch(`/api/topics/${topicId}?preview=1`, {
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window._csrf },
        });
        const data = await res.json();
        const tasks = [];
        const blocks = data.blocks || [];
        for (const block of blocks) {
          for (const zad of block.zadaniya || []) {
            for (const [idx, task] of (zad.tasks || []).entries()) {
              const preview = (task.expression || task.text || zad.instruction || '').substring(0, 60);
              tasks.push({
                block_number: block.number,
                zadanie_number: zad.number,
                task_index: idx,
                preview: `[${block.number}.${zad.number}.${idx+1}] ${preview}`,
              });
            }
          }
        }
        this.topicTasks = tasks;
      } catch (e) {
        console.warn('Failed to load topic:', e);
      }
    },

    toggleTask(topicId, idx, task) {
      if (this.isTaskSelected(topicId, idx)) {
        delete this.selectedTasks[topicId];
      } else {
        this.selectedTasks[topicId] = {
          topic_id: topicId,
          index: idx,
          block_number: task.block_number,
          zadanie_number: task.zadanie_number,
          task_index: task.task_index,
        };
      }
    },

    isTaskSelected(topicId, idx) {
      return this.selectedTasks[topicId]?.index === idx;
    },

    removeTask(topicId) {
      delete this.selectedTasks[topicId];
    },

    async createVariant() {
      if (!this.canCreate || this.creating) return;
      this.creating = true;

      try {
        const res = await window.fetchPost('/tg/admin/variants/create', {
          title: this.title.trim(),
          tasks: this.selectedTasks,
        });
        const data = await res.json();
        if (res.ok) {
          this.variants.unshift({
            id: data.variant.id,
            hash: data.variant.hash,
            title: data.variant.title,
            task_count: Object.keys(this.selectedTasks).length,
            created: new Date().toLocaleDateString('ru-RU'),
          });
          this.title = '';
          this.selectedTasks = {};
          this.activeTopic = null;
          this.tab = 'list';
        } else {
          alert(data.message || 'Ошибка создания');
        }
      } catch (e) {
        alert('Ошибка сети');
      } finally {
        this.creating = false;
      }
    },

    copyLink(hash) {
      const botUsername = '{{ config("services.telegram.bot_username", "palomatika_auth_bot") }}';
      const link = `https://t.me/${botUsername}?startapp=oge_variant_hash_${hash}`;
      navigator.clipboard.writeText(link).then(() => {
        alert('Ссылка скопирована!');
      });
    },
  };
}
</script>
@endpush
