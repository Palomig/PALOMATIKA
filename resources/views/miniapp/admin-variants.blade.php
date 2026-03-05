@extends('layouts.miniapp')
@section('title', 'Управление вариантами — palomatika')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
@endpush

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
  .create-section { display: flex; flex-direction: column; gap: 14px; padding-bottom: 80px; }
  .field-label { font-size: 12px; font-weight: 800; color: var(--text); letter-spacing: 0.03em; margin-bottom: 6px; }
  .field-input {
    width: 100%; background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 14px 16px;
    font-family: var(--body); font-size: 15px; font-weight: 700; color: var(--text);
    outline: none; transition: border-color 0.2s;
  }
  .field-input::placeholder { color: var(--muted2); font-weight: 600; }
  .field-input:focus { border-color: var(--accent); }

  /* QUICK ACTIONS */
  .quick-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 4px; }
  .qa-btn {
    padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--surface); color: var(--muted); font-size: 11px; font-weight: 700;
    cursor: pointer; transition: all 0.15s; font-family: var(--body);
  }
  .qa-btn:active { background: var(--surface2); border-color: var(--accent); color: var(--accent); }

  /* TOPIC ACCORDION */
  .topic-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; overflow: hidden; margin-bottom: 8px;
  }
  .topic-header {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px; cursor: pointer; user-select: none;
    transition: background 0.15s;
  }
  .topic-header:active { background: var(--surface2); }
  .topic-num {
    width: 32px; height: 32px; border-radius: 8px;
    background: var(--accent-bg); border: 1px solid var(--accent-bd);
    color: var(--accent); font-family: var(--display); font-size: 14px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .topic-num.has-selected { background: var(--accent); color: #fff; }
  .topic-title-wrap { flex: 1; min-width: 0; }
  .topic-title { font-size: 13px; font-weight: 700; color: var(--text); }
  .topic-sub { font-size: 10px; font-weight: 600; color: var(--muted); margin-top: 1px; }
  .topic-chevron {
    color: var(--muted); font-size: 16px; transition: transform 0.2s; flex-shrink: 0;
  }
  .topic-chevron.open { transform: rotate(180deg); }

  /* ZADANIE LIST */
  .zadanie-list { padding: 0 10px 10px; }
  .zadanie-row {
    display: flex; align-items: flex-start; gap: 10px; padding: 10px 6px;
    border-top: 1px solid var(--border); cursor: pointer;
  }
  .zadanie-row:first-child { border-top: none; }
  .zadanie-check {
    width: 20px; height: 20px; border: 2px solid var(--border);
    border-radius: 6px; flex-shrink: 0; margin-top: 1px;
    transition: all 0.15s; display: flex; align-items: center; justify-content: center;
  }
  .zadanie-check.checked { border-color: var(--accent); background: var(--accent); }
  .zadanie-check.checked::after { content: '✓'; color: #fff; font-size: 12px; font-weight: 800; }
  .zadanie-info { flex: 1; min-width: 0; }
  .zadanie-instruction { font-size: 12px; font-weight: 600; color: var(--text); line-height: 1.4; }
  .zadanie-meta { font-size: 10px; font-weight: 600; color: var(--muted); margin-top: 3px; }
  .zadanie-example {
    margin-top: 4px; padding: 4px 8px; border-radius: 6px;
    background: rgba(255,255,255,0.03); border: 1px solid var(--border);
    font-size: 11px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  }

  /* STICKY CREATE BUTTON */
  .sticky-create {
    position: fixed; bottom: 0; left: 0; right: 0;
    padding: 12px 20px calc(12px + var(--safe-bottom));
    background: var(--bg); border-top: 1px solid var(--border);
    z-index: 10;
  }
  .sticky-create .btn { width: 100%; }

  .empty-state {
    text-align: center; padding: 40px 20px;
    font-size: 13px; font-weight: 600; color: var(--muted);
  }
  .empty-icon { font-size: 40px; margin-bottom: 10px; }
@endpush

@section('body')
<div class="page admin-page" x-data="adminVariants()" x-init="$nextTick(() => renderMath())">

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
              <button class="vc-btn" @click="shareLink(v.hash)" title="Поделиться">📤</button>
              <button class="vc-btn" @click="copyLink(v.hash)" title="Скопировать">🔗</button>
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
        <div class="field-label">Выберите типы заданий</div>
        <div class="quick-actions">
          <button class="qa-btn" @click="selectAll()">Выбрать все</button>
          <button class="qa-btn" @click="selectAlgebra()">Алгебра</button>
          <button class="qa-btn" @click="selectGeometry()">Геометрия</button>
          <button class="qa-btn" @click="clearAll()">Очистить</button>
        </div>
      </div>

      {{-- TOPIC ACCORDION --}}
      @foreach($topicsWithZadaniya as $topic)
        <div class="topic-card">
          <div class="topic-header" @click="toggleTopic('{{ $topic['topic_id'] }}')">
            <div class="topic-num" :class="{ 'has-selected': hasSelectedInTopic('{{ $topic['topic_id'] }}') }">
              {{ $topic['topic_number'] }}
            </div>
            <div class="topic-title-wrap">
              <div class="topic-title">{{ $topic['title'] }}</div>
              <div class="topic-sub">
                {{ count($topic['zadaniya']) }} {{ trans_choice('тип|типа|типов', count($topic['zadaniya'])) }}
                · <span x-text="countSelectedInTopic('{{ $topic['topic_id'] }}')">0</span> выбрано
              </div>
            </div>
            <span class="topic-chevron" :class="{ open: expandedTopics.includes('{{ $topic['topic_id'] }}') }">⌄</span>
          </div>

          <div class="zadanie-list" x-show="expandedTopics.includes('{{ $topic['topic_id'] }}')" x-transition>
            @foreach($topic['zadaniya'] as $zadanie)
              <div class="zadanie-row" @click="toggleZadanie('{{ $zadanie['zadanie_id'] }}')">
                <div class="zadanie-check" :class="{ checked: selectedZadaniya.includes('{{ $zadanie['zadanie_id'] }}') }"></div>
                <div class="zadanie-info">
                  <div class="zadanie-instruction">{{ $zadanie['instruction'] }}</div>
                  <div class="zadanie-meta">{{ $zadanie['task_count'] }} {{ trans_choice('задача|задачи|задач', $zadanie['task_count']) }}</div>
                  @if($zadanie['example'])
                    <div class="zadanie-example">
                      @if($zadanie['example']['type'] === 'statements')
                        {{ Str::limit($zadanie['example']['text'], 60) }}
                      @elseif(!empty($zadanie['example']['expression']))
                        ${{ $zadanie['example']['expression'] }}$
                      @elseif(!empty($zadanie['example']['text']))
                        {{ Str::limit($zadanie['example']['text'], 60) }}
                      @endif
                    </div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach

      {{-- STICKY CREATE BUTTON --}}
      <div class="sticky-create">
        <button class="btn btn-accent" :disabled="!canCreate || creating" @click="createVariant()"
          x-text="creating ? 'Создаю...' : 'Создать вариант (' + selectedZadaniya.length + ' типов)'">
        </button>
      </div>

    </div>
  </template>

</div>
@endsection

@push('scripts')
<script>
const topicsData = @json($topicsWithZadaniya);
const algebraTopics = ['06', '07', '08', '09', '10', '11', '12', '13', '14'];
const geometryTopics = ['15', '16', '17', '18', '19'];

function adminVariants() {
  return {
    tab: '{{ ($variants ?? collect())->isEmpty() ? "create" : "list" }}',
    variants: @json($variants ?? []),
    title: '',
    selectedZadaniya: [],
    expandedTopics: [],
    creating: false,

    get canCreate() {
      return this.selectedZadaniya.length >= 1 && this.title.trim().length >= 2;
    },

    renderMath() {
      if (typeof renderMathInElement === 'function') {
        renderMathInElement(this.$el, {
          delimiters: [
            {left: '$$', right: '$$', display: true},
            {left: '$', right: '$', display: false}
          ]
        });
      }
    },

    toggleTopic(topicId) {
      const idx = this.expandedTopics.indexOf(topicId);
      if (idx > -1) this.expandedTopics.splice(idx, 1);
      else { this.expandedTopics.push(topicId); this.$nextTick(() => this.renderMath()); }
    },

    toggleZadanie(zadanieId) {
      const idx = this.selectedZadaniya.indexOf(zadanieId);
      if (idx > -1) this.selectedZadaniya.splice(idx, 1);
      else this.selectedZadaniya.push(zadanieId);
    },

    hasSelectedInTopic(topicId) {
      return this.selectedZadaniya.some(z => z.startsWith(topicId + '_'));
    },

    countSelectedInTopic(topicId) {
      return this.selectedZadaniya.filter(z => z.startsWith(topicId + '_')).length;
    },

    selectAll() {
      this.selectedZadaniya = [];
      topicsData.forEach(t => t.zadaniya.forEach(z => this.selectedZadaniya.push(z.zadanie_id)));
    },

    selectAlgebra() {
      this.selectedZadaniya = [];
      topicsData.filter(t => algebraTopics.includes(t.topic_id))
        .forEach(t => t.zadaniya.forEach(z => this.selectedZadaniya.push(z.zadanie_id)));
    },

    selectGeometry() {
      this.selectedZadaniya = [];
      topicsData.filter(t => geometryTopics.includes(t.topic_id))
        .forEach(t => t.zadaniya.forEach(z => this.selectedZadaniya.push(z.zadanie_id)));
    },

    clearAll() {
      this.selectedZadaniya = [];
    },

    async createVariant() {
      if (!this.canCreate || this.creating) return;
      this.creating = true;

      try {
        const res = await window.fetchPost('/tg/admin/variants/create', {
          title: this.title.trim(),
          zadaniya: this.selectedZadaniya,
        });
        const data = await res.json();
        if (res.ok && data.success) {
          this.variants.unshift({
            id: data.variant.id,
            hash: data.variant.hash,
            title: data.variant.title,
            task_count: data.variant.task_count || this.selectedZadaniya.length,
            created: new Date().toLocaleDateString('ru-RU'),
          });
          this.title = '';
          this.selectedZadaniya = [];
          this.tab = 'list';

          const tg = window.Telegram?.WebApp;
          if (tg && tg.showAlert) tg.showAlert('Вариант создан!');
        } else {
          const tg = window.Telegram?.WebApp;
          const msg = data.message || 'Ошибка создания';
          if (tg && tg.showAlert) tg.showAlert(msg);
          else alert(msg);
        }
      } catch (e) {
        const tg = window.Telegram?.WebApp;
        if (tg && tg.showAlert) tg.showAlert('Ошибка сети');
        else alert('Ошибка сети');
      } finally {
        this.creating = false;
      }
    },

    shareLink(hash) {
      const tg = window.Telegram?.WebApp;
      const botUsername = '{{ config("services.telegram.bot_username", "palomatika_auth_bot") }}';
      const miniAppShort = '{{ config("services.telegram.mini_app_short_name", "") }}';
      const payload = `oge_variant_hash_${hash}`;
      const link = miniAppShort
        ? `https://t.me/${botUsername}/${miniAppShort}?startapp=${payload}`
        : `https://t.me/${botUsername}?start=${payload}`;
      const text = 'Реши этот вариант ОГЭ по математике!';

      if (tg && tg.openTelegramLink) {
        const shareUrl = `https://t.me/share/url?url=${encodeURIComponent(link)}&text=${encodeURIComponent(text)}`;
        tg.openTelegramLink(shareUrl);
      } else if (navigator.share) {
        navigator.share({ title: 'Вариант ОГЭ', text: text + '\n' + link });
      }
    },

    copyLink(hash) {
      const botUsername = '{{ config("services.telegram.bot_username", "palomatika_auth_bot") }}';
      const miniAppShort = '{{ config("services.telegram.mini_app_short_name", "") }}';
      const payload = `oge_variant_hash_${hash}`;
      const link = miniAppShort
        ? `https://t.me/${botUsername}/${miniAppShort}?startapp=${payload}`
        : `https://t.me/${botUsername}?start=${payload}`;
      navigator.clipboard.writeText(link).then(() => {  
        const tg = window.Telegram?.WebApp;
        if (tg && tg.showAlert) tg.showAlert('Ссылка скопирована!');
        else alert('Ссылка скопирована!');
      });
    },
  };
}
</script>
@endpush
