@extends('layouts.pwa')
@section('title', 'Урок — palomatika')

@push('katex')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/contrib/auto-render.min.js"
        onload="renderMathInElement(document.body,{delimiters:[{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}],throwOnError:false})"></script>
@endpush

@push('styles')
  .lesson-task-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
  .lesson-task-card.is-answered { border-color: var(--accent); background: var(--accent-bg); }
  .lesson-task-num { font-family: var(--display); font-size: 18px; color: var(--accent); }
  .lesson-task-expr { font-size: 18px; color: var(--text); word-break: break-word; min-height: 24px; }
  .lesson-task-expr .katex { font-size: 1.08em; }
  .lesson-answer-row { display: flex; gap: 8px; align-items: center; }
  .lesson-answer-input { flex: 1; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 10px; padding: 12px 14px; font-size: 16px; font-family: ui-monospace, monospace; }
  .lesson-answer-input:focus { outline: 2px solid var(--accent); border-color: var(--accent); }
  .lesson-submit-btn { background: var(--accent); color: white; border: none; border-radius: 10px; padding: 12px 18px; font-weight: 800; cursor: pointer; font-size: 14px; }
  .lesson-submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
  .lesson-status-line { font-size: 12px; color: var(--muted); }
  .lesson-status-line.is-sent { color: var(--accent); font-weight: 700; }
  .lesson-choice-options { display: grid; gap: 8px; }
  .lesson-choice-option { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; cursor: pointer; }
  .lesson-choice-option.is-selected { background: var(--accent-bg); border-color: var(--accent); }
  .lesson-choice-option input[type="radio"] { accent-color: var(--accent); }
  .lesson-end-banner { background: var(--red-bg); border: 1px solid var(--red-bd); border-radius: 14px; padding: 16px; color: var(--red); font-weight: 700; text-align: center; }
@endpush

@section('body')
<div class="page" x-data="studentLesson({{ $session->id }}, '{{ $session->status }}')" x-init="init()">
  <div class="topbar">
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Урок</div>
  </div>

  <template x-if="status === 'ended'">
    <div class="lesson-end-banner">Урок завершён. Ответы больше не принимаются.</div>
  </template>

  <template x-for="task in tasks" :key="task.id">
    <div class="lesson-task-card" :class="task.my_answer ? 'is-answered' : ''">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div class="lesson-task-num" x-text="task.position + ')'"></div>
        <div class="lesson-status-line" :class="task.my_answer ? 'is-sent' : ''"
             x-text="task.my_answer ? '✓ отправлено' : 'жду ответ'"></div>
      </div>

      <div class="lesson-task-expr" x-html="renderMath(task.payload.expression)"></div>

      {{-- Choice type --}}
      <template x-if="task.payload.type === 'choice'">
        <div class="lesson-choice-options">
          <template x-for="opt in task.payload.options" :key="opt.id">
            <label class="lesson-choice-option" :class="task.my_answer === opt.id ? 'is-selected' : ''">
              <input type="radio" :name="'task_' + task.id" :value="opt.id"
                     :checked="task.my_answer === opt.id"
                     :disabled="status === 'ended'"
                     @change="submitAnswer(task.id, opt.id)">
              <span x-html="renderMath(opt.label)"></span>
            </label>
          </template>
        </div>
      </template>

      {{-- Expression type --}}
      <template x-if="task.payload.type !== 'choice'">
        <div class="lesson-answer-row">
          <input type="text" inputmode="text" class="lesson-answer-input"
                 :value="task.my_answer || ''"
                 :disabled="status === 'ended' || sending[task.id]"
                 :placeholder="task.my_answer ? '' : 'Твой ответ'"
                 @keydown.enter.prevent="submitAnswer(task.id, $event.target.value)"
                 @blur="if($event.target.value && $event.target.value !== (task.my_answer||'')) submitAnswer(task.id, $event.target.value)">
          <button class="lesson-submit-btn"
                  :disabled="status === 'ended' || sending[task.id]"
                  @click="submitAnswer(task.id, $event.target.previousElementSibling.value)">
            <span x-show="!sending[task.id]" x-text="task.my_answer ? '↻' : '→'"></span>
            <span x-show="sending[task.id]" x-cloak>…</span>
          </button>
        </div>
      </template>
    </div>
  </template>

  <template x-if="tasks.length === 0">
    <div style="text-align:center;color:var(--muted);padding:30px 0;">
      Учитель ещё не добавил задачи. Подожди немного.
    </div>
  </template>
</div>

<script>
  function studentLesson(sessionId, initialStatus) {
    return {
      sessionId,
      status: initialStatus,
      tasks: [],
      sending: {},
      pollTimer: null,

      async init() {
        await this.refreshState();
        this.pollTimer = setInterval(() => {
          if (document.hidden) return;
          this.refreshState();
        }, 5000);
      },

      async refreshState() {
        const r = await fetch(`/lessons/${this.sessionId}/state`, { headers: { 'Accept': 'application/json' }, credentials: 'include' });
        if (!r.ok) return;
        const d = await r.json();
        this.status = d.session.status;
        // Merge tasks, preserving in-progress sending state
        this.tasks = d.tasks;
        // Re-render KaTeX after data update (next tick)
        this.$nextTick(() => {
          if (window.renderMathInElement) window.renderMathInElement(document.body, { delimiters: [{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}], throwOnError: false });
        });
      },

      async submitAnswer(taskId, answer) {
        if (!answer || this.sending[taskId]) return;
        this.sending[taskId] = true;
        try {
          const r = await fetch(`/lessons/${this.sessionId}/answer`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
              'Accept': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({ task_id: taskId, answer: String(answer) }),
          });
          if (!r.ok) {
            const err = await r.json().catch(() => ({}));
            alert(err.error || 'Не удалось отправить');
            return;
          }
          // Update local copy так чтобы UI сразу обновился, без ожидания polling
          const task = this.tasks.find(t => t.id === taskId);
          if (task) task.my_answer = String(answer);
        } finally {
          this.sending[taskId] = false;
        }
      },

      renderMath(text) {
        // KaTeX renders on init; just escape and return — auto-render walks DOM
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
      },
    };
  }
</script>
@endsection
