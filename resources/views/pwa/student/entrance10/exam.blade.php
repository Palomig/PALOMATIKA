@extends('layouts.pwa')
@section('title', $title . ' — palomatika')

@include('pwa.student.entrance10._assets')

@push('styles')
  .ex-head { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
  .ex-progress { margin-left:auto; font-size:12px; font-weight:700; color:var(--muted); }
  .ex-nav { display:flex; gap:7px; overflow-x:auto; padding:4px 2px 10px; scrollbar-width:none; }
  .ex-nav::-webkit-scrollbar { display:none; }
  .ex-dot { flex:0 0 auto; width:34px; height:34px; border-radius:11px; border:1.5px solid var(--border);
    background:var(--surface); color:var(--text); font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; cursor:pointer; }
  .ex-dot.current { border-color:var(--accent); color:var(--accent); box-shadow:0 0 0 1px var(--accent) inset; }
  .ex-dot.done { background:var(--accent-bg); border-color:var(--accent-bd); color:var(--accent); }
  .ex-card { background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:16px; }
  .ex-stem { display:flex; align-items:center; gap:12px; margin-bottom:6px; }
  .ex-stem-num { display:inline-flex; align-items:center; justify-content:center; min-width:44px; height:36px; padding:0 8px;
    border-radius:12px; background:var(--accent-bg); color:var(--accent); font-family:var(--display); font-weight:800; font-size:16px; }
  .ex-stem-title { font-family:var(--display); font-size:15px; line-height:1.2; }
  .ex-part { padding:14px 0; border-top:1px dashed var(--border); }
  .ex-part:first-of-type { border-top:none; }
  .ex-part-head { display:flex; align-items:center; gap:8px; margin-bottom:7px; }
  .ex-lab { font-weight:800; color:var(--accent); font-size:15px; }
  .ex-pts { font-size:10.5px; font-weight:700; color:var(--muted); background:var(--accent-bg); border-radius:999px; padding:2px 8px; }
  .ex-text { font-size:14.5px; line-height:1.5; margin-bottom:10px; }
  .ex-qstem { margin-bottom:4px; }
  .ex-expr { font-size:17px; text-align:center; margin:2px 0 12px; overflow-x:auto; }
  .ex-expr .katex { font-size:1.12em; }
  .ex-input { width:100%; border:1.5px solid var(--border); border-radius:12px; padding:11px 12px; font-size:16px; background:var(--bg); color:var(--text); }
  .ex-input:focus { outline:none; border-color:var(--accent); }
  .ex-manual { font-size:12.5px; font-weight:600; color:var(--muted); background:var(--accent-bg); border:1px dashed var(--accent-bd); border-radius:12px; padding:10px 12px; }
  .ex-hint { font-size:11px; color:var(--muted); margin-top:6px; }
  .ex-footer { display:flex; gap:10px; margin-top:16px; }
  .ex-btn { flex:1; border:none; border-radius:14px; padding:13px; font-weight:800; font-size:15px; cursor:pointer; }
  .ex-btn-prev { background:var(--surface); border:1.5px solid var(--border); color:var(--text); }
  .ex-btn-next { background:var(--accent); color:#fff; }
  .ex-btn-finish { background:#16a34a; color:#fff; }
  .ex-modal-back { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:flex-end; justify-content:center; z-index:60; }
  .ex-modal { background:var(--surface); width:100%; max-width:480px; border-radius:22px 22px 0 0; padding:22px 18px calc(22px + var(--safe-bottom)); }
  .ex-modal h3 { font-family:var(--display); font-size:18px; margin-bottom:6px; }
  .ex-modal p { font-size:13px; color:var(--muted); margin-bottom:16px; line-height:1.5; }
  .ex-modal-row { display:flex; gap:10px; }
@endpush

@section('body')
<script>
  window.E10 = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content,
    answerUrl: @json(route('pwa.student.practice.entrance10.answer', $attempt->id)),
    submitUrl: @json(route('pwa.student.practice.entrance10.submit', $attempt->id)),
  };
</script>
<div class="page" x-data="examApp()" x-init="init()">
  <div class="ex-head">
    <a href="{{ route('pwa.student.practice.entrance10.index') }}" class="back-btn">‹</a>
    <div class="topbar-title" style="font-size:15px;">{{ $title }}</div>
    <div class="ex-progress" x-text="'отвечено: ' + answeredCount + ' / ' + scorableTotal"></div>
  </div>

  <div class="ex-nav">
    <template x-for="(g, i) in groups" :key="g.number">
      <div class="ex-dot" :class="{ current: i === current, done: groupAnswered(g) }" x-text="g.number" @click="go(i)"></div>
    </template>
  </div>

  <div class="ex-card" x-ref="card">
    <div class="ex-stem">
      <span class="ex-stem-num" x-text="'№' + currentGroup.number"></span>
      <div class="ex-stem-title" x-text="currentGroup.title"></div>
    </div>
    <template x-if="currentGroup.stem">
      <div class="ex-qstem">
        <div class="ex-text" x-html="currentGroup.stem.instruction"></div>
        <template x-if="currentGroup.stem.expression"><div class="ex-expr" x-html="currentGroup.stem.expression"></div></template>
      </div>
    </template>
    <template x-for="part in currentGroup.parts" :key="part.slot">
      <div class="ex-part">
        <div class="ex-part-head">
          <template x-if="part.label"><span class="ex-lab" x-text="part.label + ')'"></span></template>
          <span class="ex-pts" x-text="part.points + ' ' + ballWord(part.points)"></span>
        </div>
        <template x-if="part.instruction">
          <div>
            <div class="ex-text" x-html="part.instruction"></div>
            <template x-if="part.expression"><div class="ex-expr" x-html="part.expression"></div></template>
          </div>
        </template>
        <template x-if="!part.instruction">
          <div class="ex-text" x-html="part.text"></div>
        </template>
        <template x-if="part.manual">
          <div class="ex-manual">✍️ Построй график на бумаге — этот пункт проверяется вручную.</div>
        </template>
        <template x-if="!part.manual">
          <div>
            <input class="ex-input" type="text" inputmode="text" autocomplete="off" autocapitalize="off" spellcheck="false"
                   placeholder="Ответ"
                   :data-mathpad="part.check === 'number_set' ? 'full' : (part.check === 'number' ? 'roots' : null)"
                   x-model="answers[part.slot]"
                   @input.debounce.700ms="save(part.slot)"
                   @blur="save(part.slot)">
            <template x-if="part.check === 'number_set'"><div class="ex-hint">Несколько ответов — через «;» или пробел. Корень: √6 или sqrt6.</div></template>
            <template x-if="part.check === 'param_condition'"><div class="ex-hint">Например: b ≠ 1 или b &gt; 0, b ≠ 1.</div></template>
          </div>
        </template>
      </div>
    </template>
  </div>

  <div class="ex-footer">
    <button class="ex-btn ex-btn-prev" @click="prev()" x-show="current > 0">← Назад</button>
    <button class="ex-btn ex-btn-next" @click="next()" x-show="current < groups.length - 1">Дальше →</button>
    <button class="ex-btn ex-btn-finish" @click="openConfirm()" x-show="current === groups.length - 1">Завершить</button>
  </div>

  <template x-if="showConfirm">
    <div class="ex-modal-back" @click.self="showConfirm = false">
      <div class="ex-modal">
        <h3>Завершить работу?</h3>
        <p x-text="'Отвечено ' + answeredCount + ' из ' + scorableTotal + ' пунктов. После завершения будут показаны ошибки.'"></p>
        <div class="ex-modal-row">
          <button class="ex-btn ex-btn-prev" @click="showConfirm = false">Ещё подумать</button>
          <button class="ex-btn ex-btn-finish" @click="submit()" :disabled="submitting" x-text="submitting ? 'Отправка…' : 'Завершить'"></button>
        </div>
      </div>
    </div>
  </template>
</div>

<script>
function examApp() {
  return {
    groups: @json($groups),
    answers: Object.assign({}, @json($answers ?? (object)[])),
    current: 0,
    submitting: false,
    showConfirm: false,

    init() { this.$nextTick(() => this.renderMath()); },
    get currentGroup() { return this.groups[this.current] || { number: '', title: '', parts: [] }; },
    get scorableTotal() { let n = 0; this.groups.forEach(g => g.parts.forEach(p => { if (!p.manual) n++; })); return n; },
    get answeredCount() {
      let n = 0;
      this.groups.forEach(g => g.parts.forEach(p => {
        if (!p.manual && this.answers[p.slot] !== undefined && String(this.answers[p.slot]).trim() !== '') n++;
      }));
      return n;
    },
    ballWord(n) { n = Math.abs(n) % 100; if (n >= 11 && n <= 14) return 'баллов'; const d = n % 10; return d === 1 ? 'балл' : (d >= 2 && d <= 4 ? 'балла' : 'баллов'); },
    groupAnswered(g) { return g.parts.some(p => !p.manual && this.answers[p.slot] !== undefined && String(this.answers[p.slot]).trim() !== ''); },
    renderMath() {
      if (window.renderMathInElement && this.$refs.card) {
        try { window.renderMathInElement(this.$refs.card, { delimiters: [
          {left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false},
          {left:'\\(',right:'\\)',display:false},{left:'\\[',right:'\\]',display:true}] }); } catch (e) {}
      }
    },
    go(i) { this.current = Math.max(0, Math.min(this.groups.length - 1, i)); this.$nextTick(() => { this.renderMath(); window.scrollTo(0, 0); }); },
    prev() { this.go(this.current - 1); },
    next() { this.go(this.current + 1); },
    save(slot) {
      const val = this.answers[slot] === undefined ? '' : String(this.answers[slot]);
      fetch(window.E10.answerUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.E10.csrf, 'Accept': 'application/json' },
        body: JSON.stringify({ slot: Number(slot), answer: val }),
      }).catch(() => {});
    },
    openConfirm() { this.showConfirm = true; },
    submit() {
      if (this.submitting) return;
      this.submitting = true;
      fetch(window.E10.submitUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.E10.csrf, 'Accept': 'application/json' },
        body: JSON.stringify({ answers: this.answers }),
      })
      .then(r => r.json())
      .then(d => { if (d.redirect) location.href = d.redirect; else throw new Error(); })
      .catch(() => { this.submitting = false; alert('Не удалось завершить. Попробуйте ещё раз.'); });
    },
  };
}
</script>
@endsection
