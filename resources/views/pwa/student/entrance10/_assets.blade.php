{{-- Общие стили и скрипты раздела «Контрольные для 10 класса». --}}
@push('katex')
  @include('partials.head-katex')
@endpush

@include('partials.math-answer-pad')

@push('styles')
  .e10-intro {
    background: linear-gradient(180deg, rgba(79,142,247,.10), transparent), var(--surface);
    border: 1.5px solid var(--accent-bd); border-radius: 20px; padding: 18px 18px 16px; margin-bottom: 18px;
  }
  .e10-intro-title { font-family: var(--display); font-size: 18px; line-height: 1.25; margin-bottom: 4px; }
  .e10-intro-sub { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 12px; }
  .e10-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
  .e10-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700;
    background: var(--accent-bg); color: var(--accent); border-radius: 999px; padding: 5px 11px; }
  .e10-rules { margin: 0; padding-left: 18px; font-size: 12px; font-weight: 600; color: var(--muted); line-height: 1.55; }
  .e10-rules li { margin-bottom: 3px; }

  .e10-section-title { font-family: var(--display); font-size: 15px; margin: 20px 2px 10px; }
  .e10-cards { display: flex; flex-direction: column; gap: 12px; }
  .e10-card {
    display: flex; align-items: center; gap: 14px; text-decoration: none; color: inherit;
    background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 16px;
  }
  .e10-card-icon { width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0; background: var(--accent-bg);
    display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; color: var(--accent); }
  .e10-card-title { font-family: var(--display); font-size: 15px; margin-bottom: 2px; }
  .e10-card-desc { font-size: 11.5px; font-weight: 600; color: var(--muted); }
  .e10-card-go { margin-left: auto; color: var(--muted); font-size: 20px; }

  .e10-num-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
  .e10-num-cell {
    display: flex; flex-direction: column; gap: 4px; text-decoration: none; color: inherit;
    background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 12px; min-height: 78px;
  }
  .e10-num-cell.is-active { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent) inset; }
  .e10-num-badge { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px;
    border-radius: 9px; background: var(--accent-bg); color: var(--accent); font-weight: 800; font-size: 14px; }
  .e10-num-label { font-size: 11px; font-weight: 700; line-height: 1.25; color: var(--text); }
  .e10-num-tag { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
  .e10-num-tag.gen { color: #16a34a; } .e10-num-tag.stat { color: var(--muted); }

  .e10-task-index { font-size: 11px; font-weight: 800; color: var(--muted); margin: 2px 4px -6px; }
  .e10-task { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 16px; margin-bottom: 14px; }
  .e10-task-head { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
  .e10-num { display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 34px; padding: 0 8px;
    border-radius: 11px; background: var(--accent-bg); color: var(--accent); font-family: var(--display); font-weight: 800; font-size: 15px; }
  .e10-task-title { font-family: var(--display); font-size: 15px; line-height: 1.2; }
  .e10-task-source { font-size: 10.5px; font-weight: 700; color: var(--muted); margin-top: 2px; }
  .e10-task-source.is-gen { color: #16a34a; }

  .e10-part { padding: 12px 0; border-top: 1px dashed var(--border); }
  .e10-part:first-of-type { border-top: none; }
  .e10-part-head { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
  .e10-label { font-weight: 800; color: var(--accent); font-size: 14px; }
  .e10-points { font-size: 10.5px; font-weight: 700; color: var(--muted); background: var(--accent-bg); border-radius: 999px; padding: 2px 8px; }
  .e10-text { font-size: 14.5px; line-height: 1.5; margin-bottom: 10px; }
  .e10-text .katex { font-size: 1.02em; }
  .e10-stem { font-size: 14.5px; line-height: 1.5; margin-bottom: 6px; }
  .e10-expr { font-size: 16px; text-align: center; margin: 4px 0 12px; overflow-x: auto; }
  .e10-expr .katex { font-size: 1.1em; }

  .e10-input-row { display: flex; gap: 8px; }
  .e10-input { flex: 1; min-width: 0; border: 1.5px solid var(--border); border-radius: 12px; padding: 10px 12px;
    font-size: 15px; background: var(--bg); color: var(--text); }
  .e10-input:focus { outline: none; border-color: var(--accent); }
  .e10-btn { flex-shrink: 0; border: none; border-radius: 12px; padding: 10px 16px; font-weight: 800; font-size: 14px;
    background: var(--accent); color: #fff; cursor: pointer; }
  .e10-btn:disabled { opacity: .5; }
  .e10-btn-ghost { display: inline-block; margin-top: 8px; border: none; background: none; padding: 4px 0;
    color: var(--muted); font-weight: 700; font-size: 12.5px; cursor: pointer; text-decoration: underline; }
  .e10-hint { font-size: 11px; color: var(--muted); margin-top: 6px; }
  .e10-hint code { background: var(--accent-bg); border-radius: 5px; padding: 1px 5px; }

  .e10-result { margin-top: 10px; border-radius: 12px; padding: 11px 13px; font-size: 13.5px; line-height: 1.5; }
  .e10-result.ok { background: rgba(22,163,74,.10); border: 1px solid rgba(22,163,74,.35); }
  .e10-result.bad { background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.3); }
  .e10-result.neutral { background: var(--accent-bg); border: 1px solid var(--accent-bd); }
  .e10-result-head { font-weight: 800; margin-bottom: 4px; }
  .e10-result.ok .e10-result-head { color: #16a34a; }
  .e10-result.bad .e10-result-head { color: #dc2626; }
  .e10-result-answer { margin-bottom: 6px; }
  .e10-result-solution { color: var(--text); opacity: .92; }
  .e10-result-solution b { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: var(--muted); margin-bottom: 2px; }

@endpush

@push('scripts')
<script>
(function () {
  const cfg = window.E10 || {};
  function ballWord(n) {
    n = Math.abs(n) % 100;
    if (n >= 11 && n <= 14) return 'баллов';
    const d = n % 10;
    return d === 1 ? 'балл' : (d >= 2 && d <= 4 ? 'балла' : 'баллов');
  }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  function renderMath(el) {
    if (window.renderMathInElement) {
      try {
        window.renderMathInElement(el, { delimiters: [
          {left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false},
          {left:'\\(',right:'\\)',display:false},{left:'\\[',right:'\\]',display:true}
        ]});
      } catch (e) {}
    }
  }

  function showResult(partEl, data, wasReveal) {
    const box = partEl.querySelector('.e10-result');
    if (!box) return;
    let cls = 'neutral', head = '';
    if (!wasReveal && data.status === 'checked') {
      if (data.correct) { cls = 'ok'; head = '✓ Верно!'; }
      else { cls = 'bad'; head = '✗ Неверно, попробуйте ещё раз'; }
    } else {
      cls = 'neutral'; head = 'Ответ';
    }
    let html = '<div class="e10-result-head">' + head + '</div>';
    if (data.answer_display) {
      html += '<div class="e10-result-answer"><b style="display:inline">Ответ:</b> ' + data.answer_display + '</div>';
    }
    if (data.solution) {
      html += '<div class="e10-result-solution"><b>Решение</b>' + data.solution + '</div>';
    }
    box.className = 'e10-result ' + cls;
    box.innerHTML = html;
    box.hidden = false;
    renderMath(box);
  }

  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' },
      body: JSON.stringify(body)
    }).then(r => r.json());
  }

  function handleCheck(partEl, reveal) {
    const token = partEl.getAttribute('data-token');
    const input = partEl.querySelector('.e10-input');
    const answer = input ? input.value : '';
    if (!reveal && input && answer.trim() === '') { input.focus(); return; }
    const btn = partEl.querySelector(reveal ? '.e10-reveal' : '.e10-check');
    if (btn) btn.disabled = true;
    post(cfg.checkUrl, { token, answer, reveal: !!reveal })
      .then(data => { showResult(partEl, data, reveal); })
      .catch(() => {})
      .finally(() => { if (btn) btn.disabled = false; });
  }

  document.addEventListener('click', function (e) {
    const check = e.target.closest('.e10-check');
    if (check) { handleCheck(check.closest('.e10-part'), false); return; }
    const reveal = e.target.closest('.e10-reveal');
    if (reveal) { handleCheck(reveal.closest('.e10-part'), true); return; }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.classList && e.target.classList.contains('e10-input')) {
      e.preventDefault();
      handleCheck(e.target.closest('.e10-part'), false);
    }
  });
})();
</script>
@endpush
