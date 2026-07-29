@extends('layouts.pwa')
@section('title', '2я часть ОГЭ — palomatika')

@include('partials.math-answer-pad')

@push('katex')
@include('partials.head-katex')
@endpush

@push('styles')
  /* Условия банка ФИПИ приходят готовой разметкой: формулы в KaTeX, чертежи
     инлайновыми SVG. Ширину чертежа задают Tailwind-классы (`max-w-[350px]`),
     но в PWA Tailwind не подключён — он живёт в head-config, а тут своя тема.
     Без этих правил SVG с одним viewBox схлопывается в нулевую высоту, и
     ученик видит условие без рисунка. */
  .fipi-drawing {
    margin: 0 0 10px; padding: 8px; border: 1px solid var(--border);
    border-radius: 10px; background: #0a1628;
  }
  .fipi-drawing svg { width: 100%; height: auto; display: block; margin: 0 auto; max-width: 460px; }
  .fipi-html svg { width: 100%; height: auto; display: block; margin: 0 auto; max-width: 350px; }
  .fipi-html svg[class*="max-w-[250px]"] { max-width: 250px; }
  .fipi-html svg[class*="max-w-[280px]"] { max-width: 280px; }
  .fipi-html svg[class*="max-w-[320px]"] { max-width: 320px; }
  .fipi-html svg[class*="max-w-[420px]"] { max-width: 420px; }
  .fipi-html svg[class*="max-w-[1200px]"] { max-width: 100%; }
  .fipi-html img { max-width: 100%; height: auto; }
  .fipi-html p { margin: 0 0 8px; }
  .fipi-html p:last-child { margin-bottom: 0; }
  .fipi-html table { border-collapse: collapse; max-width: 100%; }
  .fipi-html td { vertical-align: top; padding: 2px 6px; }
  /* Условие и чертёж лежат в соседних ячейках таблицы — на телефоне они
     не помещаются рядом, поэтому раскладываем в столбик. */
  @media (max-width: 640px) {
    .fipi-html table, .fipi-html tbody, .fipi-html tr, .fipi-html td {
      display: block; width: 100%; padding-left: 0; padding-right: 0;
    }
  }
  .teacher-solution-btn {
    display: inline-flex; align-items: center; gap: 8px;
    margin-bottom: 12px; padding: 9px 14px; border-radius: 10px;
    background: linear-gradient(135deg, #1e3a5f, #2a4d7a);
    border: 1px solid #3a6098; color: #dbe9ff;
    font-family: var(--display); font-size: 14px; font-weight: 600;
    text-decoration: none; transition: all .15s ease;
  }
  .teacher-solution-btn:active { transform: scale(.97); }
  .teacher-solution-tag {
    font-size: 11px; font-weight: 500; opacity: .7;
    padding: 2px 7px; border-radius: 6px; background: rgba(255,255,255,.12);
  }
  .topics-row {
    display: grid; grid-auto-flow: column; grid-auto-columns: 1fr;
    gap: 6px; padding-bottom: 2px;
    opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards;
  }
  .topic-chip {
    display: flex; align-items: center; justify-content: center;
    padding: 10px 4px; border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text); text-decoration: none;
    font-family: var(--display); font-size: 14px;
    transition: all .15s ease;
    min-width: 0;
  }
  .topic-chip.active {
    border-color: #d1d5db;
    background: #d1d5db;
    color: #1c1f27;
  }
  .topic-chip.active .topic-chip-num { color: #1c1f27; }
  .topic-chip.disabled {
    opacity: 0.4; cursor: default; pointer-events: none;
  }
  .topic-chip-num { font-weight: 700; }

  .spoiler {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
  }
  .spoiler summary {
    list-style: none;
    cursor: pointer;
    padding: 14px 16px;
    font-family: var(--display);
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 14px;
  }
  .spoiler summary::-webkit-details-marker { display: none; }
  .spoiler-num {
    flex-shrink: 0;
    font-size: 16px;
    font-weight: 800;
    color: var(--muted);
    min-width: 28px;
    text-align: left;
    line-height: 1;
    font-variant-numeric: tabular-nums;
  }
  .spoiler-body-text {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: 2px;
  }
  .spoiler-title {
    font-size: 14px;
    line-height: 1.3;
    color: var(--text);
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .spoiler-subtitle {
    font-family: inherit;
    font-size: 11px;
    color: var(--muted);
    line-height: 1.3;
  }
  .spoiler-chevron {
    flex-shrink: 0;
    font-size: 20px;
    color: var(--muted);
    line-height: 1;
    transition: transform .15s ease;
  }
  .spoiler[open] .spoiler-chevron { transform: rotate(90deg); }
  .spoiler-body { padding: 0 10px 10px; display: flex; flex-direction: column; gap: 8px; }
  .task-list {
    margin-top: 12px;
    display: flex; flex-direction: column; gap: 8px;
    opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards;
  }
  .bank-section-title {
    margin: 12px 2px 2px;
    font-family: var(--display); font-size: 14px; font-weight: 700;
    color: var(--text);
  }
  .bank-section-title:first-child { margin-top: 0; }
  .task-item {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px 14px;
  }
  .task-item-text { font-size: 13px; line-height: 1.45; color: var(--text); }
  .task-item-meta { margin-top: 6px; font-size: 10px; color: var(--muted); font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }

  .hint-box {
    font-size: 11px; color: var(--muted); font-style: italic;
    padding: 6px 10px; margin-bottom: 4px;
  }

  .answer-row { margin-top: 8px; display: flex; align-items: center; gap: 8px; }
  .answer-label { font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
  .answer-value { font-family: var(--display); font-size: 14px; color: var(--green); }
  .answer-blur { filter: blur(6px); user-select: none; pointer-events: none; color: var(--text); font-family: var(--display); font-size: 14px; }
  .premium-cta { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: var(--purple); cursor: pointer; white-space: nowrap; }

  .p2-answer { margin-top: 10px; }
  .p2-input-row { display: flex; gap: 8px; }
  .p2-input { flex: 1; min-width: 0; border: 1.5px solid var(--border); border-radius: 10px; padding: 9px 11px;
    font-size: 14px; background: var(--bg); color: var(--text); }
  .p2-input:focus { outline: none; border-color: var(--accent); }
  .p2-btn { flex-shrink: 0; border: none; border-radius: 10px; padding: 9px 14px; font-weight: 800; font-size: 13px;
    background: var(--accent); color: #fff; cursor: pointer; }
  .p2-btn:disabled { opacity: .5; }
  .p2-hint { font-size: 10.5px; color: var(--muted); margin-top: 6px; line-height: 1.4; }
  .p2-reveal { display: inline-block; margin-top: 8px; border: none; background: none; padding: 2px 0;
    color: var(--muted); font-weight: 700; font-size: 12px; cursor: pointer; text-decoration: underline; }
  .p2-result { margin-top: 9px; border-radius: 10px; padding: 9px 11px; font-size: 13px; line-height: 1.45; }
  .p2-result.ok { background: rgba(22,163,74,.10); border: 1px solid rgba(22,163,74,.35); color: var(--green); font-weight: 700; }
  .p2-result.bad { background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.3); color: #dc2626; font-weight: 700; }
  .p2-result.neutral { background: var(--accent-bg); border: 1px solid var(--accent-bd); color: var(--text); }

  .pm-overlay { position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,.55); backdrop-filter: blur(4px); display: flex; align-items: flex-end; justify-content: center; }
  .pm-sheet { background: var(--bg); border-radius: 20px 20px 0 0; width: 100%; max-width: 420px; padding: 24px 20px 32px; }
  .pm-handle { width: 36px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 16px; }
  .pm-title { font-family: var(--display); font-size: 20px; color: var(--text); text-align: center; margin-bottom: 8px; }
  .pm-desc { font-size: 13px; color: var(--muted); text-align: center; line-height: 1.5; margin-bottom: 20px; }
  .pm-price { font-family: var(--display); font-size: 28px; color: var(--text); text-align: center; margin-bottom: 20px; }
  .pm-price small { font-size: 14px; color: var(--muted); }
  .pm-btn { display: block; width: 100%; padding: 16px; border: none; border-radius: 14px; font-family: var(--display); font-size: 15px; cursor: pointer; text-align: center; margin-bottom: 10px; }
  .pm-btn-primary { background: var(--purple); color: #fff; }
  .pm-btn-primary:active { filter: brightness(0.9); }
  .pm-btn-trial { background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple); }
  .pm-btn-trial:active { filter: brightness(0.9); }
  .pm-cancel { display: block; width: 100%; padding: 14px; background: none; border: none; color: var(--muted); font-size: 14px; font-weight: 700; cursor: pointer; }

  .page-header { display: flex; align-items: center; gap: 12px; opacity: 0; animation: fadeDown 0.3s ease forwards; }
  .page-header .back-btn { width: 42px; height: 42px; font-size: 26px; font-weight: 700; color: var(--text); }
  .page-header .header-title { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
  .page-header .header-name { font-family: var(--display); font-size: 15px; color: var(--text); line-height: 1.2; }
  .page-header .header-desc { font-size: 11px; font-weight: 600; color: var(--muted); line-height: 1.3; }
@endpush

@section('body')
<div class="page task-render-scope" x-data="taskBrowser()">
  <div class="page-header">
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">‹</a>
    <div class="header-title">
      <div class="header-name">2я часть ОГЭ</div>
      <div class="header-desc">Задания 20–25</div>
    </div>
  </div>

  <div class="sec-label">Выбери тему</div>
  <div class="topics-row">
    @foreach($topicsMeta as $tid => $meta)
      @php $tid = (string) $tid; @endphp
      <a class="topic-chip {{ $selectedTopic === $tid ? 'active' : '' }} {{ !in_array($tid, $topics) ? 'disabled' : '' }}"
         href="{{ in_array($tid, $topics) ? url('/part2?topic=' . $tid) : '#' }}">
        <span class="topic-chip-num">{{ $tid }}</span>
      </a>
    @endforeach
    {{-- Show coming soon topics --}}
    @foreach(['22', '24', '25'] as $tid)
      @if(!isset($topicsMeta[$tid]))
        <span class="topic-chip disabled">
          <span class="topic-chip-num">{{ $tid }}</span>
        </span>
      @endif
    @endforeach
  </div>

  <div class="sec-label" style="margin-top:14px;">
    {{ $topicsMeta[$selectedTopic]['icon'] ?? '' }}
    {{ $topicsMeta[$selectedTopic]['title'] ?? 'Тема ' . $selectedTopic }}
  </div>

  <div class="task-list">
    @php
      $lastSection = null;
    @endphp
    @forelse($zadaniya as $group)
      @if(($group['section'] ?? '') !== '' && $group['section'] !== $lastSection)
        <div class="bank-section-title">{{ $group['section'] }}</div>
        @php
          $lastSection = $group['section'];
        @endphp
      @endif
      <details class="spoiler">
        <summary>
          <span class="spoiler-num">{{ (int)($group['number'] ?? $loop->iteration) }}</span>
          <span class="spoiler-body-text">
            <span class="spoiler-title">{{ $group['title'] }}</span>
            @if(!empty($group['subtitle']))
              <span class="spoiler-subtitle">{{ $group['subtitle'] }}</span>
            @endif
          </span>
          <span class="spoiler-chevron">›</span>
        </summary>
        <div class="spoiler-body">
          @if($isTeacher && !empty($group['has_solution']))
            <a class="teacher-solution-btn"
               href="{{ route('pwa.student.part2.solution', ['topic' => $selectedTopic, 'number' => $group['number']]) }}">
              📖 Подробнее <span class="teacher-solution-tag">для учителя</span>
            </a>
          @endif
          @if($group['hint'])
            <div class="hint-box">{{ $group['hint'] }}</div>
          @endif
          @foreach($group['tasks'] as $task)
            <div class="task-item">
              @if(!empty($task['image']))
                <img src="{{ asset('images/tasks/' . $selectedTopic . '/' . ltrim($task['image'], '/')) }}"
                     alt="" style="display:block;max-width:100%;height:auto;margin-bottom:10px;border:1px solid var(--border);border-radius:10px;background:#fff;padding:4px;" loading="lazy">
              @endif
              @if(!empty($task['drawing']))
                <div class="fipi-drawing">{!! $task['drawing'] !!}</div>
              @endif

              @if(!empty($task['html']))
                {{-- Банк ФИПИ: разметка уже готова (KaTeX + инлайновые SVG) --}}
                <div class="task-item-text fipi-html">{!! $task['html'] !!}</div>
              @else
                <div class="task-item-text">{{ $task['text'] }}</div>
              @endif
              @if(!empty($task['answer']))
                @if($isTeacher)
                  {{-- Учителю ответ нужен как справка — показываем сразу. --}}
                  <div class="answer-row">
                    <span class="answer-label">Ответ:</span>
                    <span class="answer-value">{{ $task['answer'] }}</span>
                  </div>
                @else
                  <div class="p2-answer" data-zadanie="{{ $group['number'] }}" data-task="{{ $task['id'] }}">
                    <div class="p2-input-row" data-mathpad-anchor>
                      <input type="text" class="p2-input" placeholder="Твой ответ"
                             autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="text"
                             @if(in_array($selectedTopic, ['20', '23'], true))
                               data-mathpad="{{ $selectedTopic === '20' ? 'full' : 'roots' }}"
                             @endif>
                      <button type="button" class="p2-btn p2-check">Проверить</button>
                    </div>
                    @if($selectedTopic === '20')
                      <div class="p2-hint">Несколько корней — через «;». Промежуток — со скобками: (1; 1+√2).</div>
                    @elseif($selectedTopic === '23')
                      <div class="p2-hint">Корень пиши как √6 или sqrt(6). Ответ нужен точный, не десятичный.</div>
                    @endif
                    <div class="p2-result" hidden></div>
                    <button type="button" class="p2-reveal">Показать ответ</button>
                  </div>
                @endif
              @endif
              @if(!empty($task['id']))
                <div class="task-item-meta">{{ $task['id'] }}</div>
              @endif
            </div>
          @endforeach
        </div>
      </details>
    @empty
      <div class="task-item">
        <div class="task-item-text">Задания для этой темы скоро появятся.</div>
      </div>
    @endforelse
  </div>

  <template x-if="showPremium">
    <div class="pm-overlay" @click.self="showPremium = false">
      <div class="pm-sheet">
        <div class="pm-handle"></div>
        <div class="pm-title">Premium</div>
        <div class="pm-desc">Открой ответы ко всем заданиям в базе.<br>Подписка на 30 дней.</div>
        <div class="pm-price">100 ⭐ <small>/ мес</small></div>
        <button class="pm-btn pm-btn-primary" @click="buyPremium()" :disabled="buying" x-text="buying ? 'Загрузка...' : 'Купить'"></button>
        @if(!$trialUsed)
        <button class="pm-btn pm-btn-trial" @click="activateTrial()" :disabled="buying" x-text="trialActivating ? 'Активация...' : '7 дней бесплатно'"></button>
        @endif
        <button class="pm-cancel" @click="showPremium = false">Отмена</button>
      </div>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
  function taskBrowser() {
    return {
      showPremium: false,
      buying: false,
      trialActivating: false,
      async buyPremium() {
        if (this.buying) return;
        this.buying = true;
        try {
          const res = await window.fetchPost('/premium/buy');
          const data = await res.json();
          if (data.invoice_url) {
            const tg = window.Telegram?.WebApp;
            if (tg && tg.openInvoice) {
              tg.openInvoice(data.invoice_url, (status) => {
                if (status === 'paid') window.location.reload();
                this.buying = false;
              });
            } else { window.open(data.invoice_url, '_blank'); this.buying = false; }
          } else { alert(data.error || 'Ошибка'); this.buying = false; }
        } catch (e) { alert('Ошибка соединения'); this.buying = false; }
      },
      async activateTrial() {
        if (this.trialActivating) return;
        this.trialActivating = true;
        try {
          const res = await window.fetchPost('/premium/trial');
          const data = await res.json();
          if (data.ok) window.location.reload();
          else { alert(data.error || 'Ошибка'); this.trialActivating = false; }
        } catch (e) { alert('Ошибка соединения'); this.trialActivating = false; }
      },
    };
  }

  // Проверка ответа к заданию второй части. Сверка на сервере — там общий
  // разбор выражений с корнями, свой в JS дублировать нельзя.
  (function () {
    const TOPIC = @json($selectedTopic);
    const URL = @json(route('pwa.student.part2.check'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

    function show(box, cls, text) {
      box.className = 'p2-result ' + cls;
      box.textContent = text;
      box.hidden = false;
    }

    async function run(wrap, reveal) {
      const input = wrap.querySelector('.p2-input');
      const box = wrap.querySelector('.p2-result');
      const answer = input ? input.value.trim() : '';
      if (!reveal && answer === '') { input?.focus(); return; }

      const btn = wrap.querySelector(reveal ? '.p2-reveal' : '.p2-check');
      if (btn) btn.disabled = true;
      try {
        const res = await fetch(URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
          body: JSON.stringify({
            topic: TOPIC,
            zadanie: Number(wrap.dataset.zadanie),
            task_id: Number(wrap.dataset.task),
            answer,
            reveal: !!reveal,
          }),
        });
        const data = await res.json();
        if (!res.ok) { show(box, 'neutral', 'Не удалось проверить, попробуй ещё раз'); return; }
        if (data.status === 'revealed') show(box, 'neutral', 'Ответ: ' + data.answer);
        else if (data.correct) show(box, 'ok', '✓ Верно!');
        else show(box, 'bad', '✗ Неверно, попробуй ещё раз');
      } catch (e) {
        show(box, 'neutral', 'Нет связи с сервером');
      } finally {
        if (btn) btn.disabled = false;
      }
    }

    document.addEventListener('click', function (e) {
      const check = e.target.closest('.p2-check');
      if (check) { run(check.closest('.p2-answer'), false); return; }
      const reveal = e.target.closest('.p2-reveal');
      if (reveal) { run(reveal.closest('.p2-answer'), true); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && e.target.classList?.contains('p2-input')) {
        e.preventDefault();
        run(e.target.closest('.p2-answer'), false);
      }
    });
  })();
</script>
@endpush
