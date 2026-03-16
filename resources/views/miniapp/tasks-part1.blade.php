@extends('layouts.miniapp')
@section('title', '1я часть ОГЭ — palomatika')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<style>
  .topics-row {
    display: flex; gap: 6px; overflow-x: auto; padding-bottom: 2px;
    opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards;
  }
  .topic-pill {
    min-width: 42px; text-align: center;
    padding: 8px 10px; border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text); text-decoration: none;
    font-family: var(--display); font-size: 14px;
  }
  .topic-pill.active {
    border-color: var(--purple-bd);
    background: var(--purple-bg);
    color: var(--purple);
  }

  .spoiler {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
  }
  .spoiler summary {
    list-style: none; cursor: pointer;
    padding: 12px 14px;
    font-family: var(--display); font-size: 13px; color: var(--text);
    display: flex; justify-content: space-between; align-items: center; gap: 8px;
  }
  .spoiler summary::-webkit-details-marker { display: none; }
  .spoiler summary::after {
    content: '▾'; color: var(--muted);
    transition: transform .15s ease; flex-shrink: 0;
  }
  .spoiler[open] summary::after { transform: rotate(180deg); }
  .spoiler-body { padding: 0 10px 10px; display: flex; flex-direction: column; gap: 8px; }

  .task-list {
    margin-top: 12px;
    display: flex; flex-direction: column; gap: 8px;
    opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards;
  }
  .task-item {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px 14px;
  }
  .task-item-text { font-size: 13px; line-height: 1.45; color: var(--text); }
  .task-item-meta { margin-top: 6px; font-size: 10px; color: var(--muted); font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }

  .answer-row { margin-top: 8px; display: flex; align-items: center; gap: 8px; }
  .answer-label { font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
  .answer-value { font-family: var(--display); font-size: 14px; color: var(--green); }
  .answer-blur { filter: blur(6px); user-select: none; pointer-events: none; color: var(--text); font-family: var(--display); font-size: 14px; }
  .premium-cta { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: var(--purple); cursor: pointer; white-space: nowrap; }

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
</style>
@endpush

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.answer-value, .answer-blur').forEach(function (el) {
      var t = el.textContent.trim();
      if (!t || /^\$/.test(t)) return;
      t = t.replace(/sqrt\(([^)]+)\)/g, '\\sqrt{$1}');
      el.textContent = '$' + t + '$';
    });
    if (typeof renderMathInElement === 'function') {
      document.querySelectorAll('.task-render-scope').forEach(function (el) {
        renderMathInElement(el, {
          delimiters: [
            { left: '$$', right: '$$', display: true },
            { left: '\\[', right: '\\]', display: true },
            { left: '\\(', right: '\\)', display: false },
            { left: '$', right: '$', display: false }
          ],
          throwOnError: false
        });
      });
    }
  });

  function taskBrowser() {
    return {
      showPremium: false,
      buying: false,
      trialActivating: false,
      async buyPremium() {
        if (this.buying) return;
        this.buying = true;
        try {
          const res = await window.fetchPost('/tg/premium/buy');
          const data = await res.json();
          if (data.invoice_url) {
            const tg = window.Telegram?.WebApp;
            if (tg && tg.openInvoice) {
              tg.openInvoice(data.invoice_url, (status) => {
                if (status === 'paid') window.location.reload();
                this.buying = false;
              });
            } else {
              window.open(data.invoice_url, '_blank');
              this.buying = false;
            }
          } else { alert(data.error || 'Ошибка'); this.buying = false; }
        } catch (e) { alert('Ошибка соединения'); this.buying = false; }
      },
      async activateTrial() {
        if (this.trialActivating) return;
        this.trialActivating = true;
        try {
          const res = await window.fetchPost('/tg/premium/trial');
          const data = await res.json();
          if (data.ok) window.location.reload();
          else { alert(data.error || 'Ошибка'); this.trialActivating = false; }
        } catch (e) { alert('Ошибка соединения'); this.trialActivating = false; }
      },
    };
  }
</script>
@endpush

@section('body')
<div class="page task-render-scope" x-data="taskBrowser()">
  <a href="/tg/dashboard" class="back-btn">‹</a>

  <div class="hero" style="opacity:0; animation: fadeUp 0.3s ease 0.04s forwards;">
    <div class="hero-title">1я часть ОГЭ</div>
    <div class="hero-sub">задания 6–19 · {{ $taskCount }} заданий</div>
  </div>

  <div class="sec-label">Выбери задание</div>
  <div class="topics-row">
    @foreach($topicIds as $tid)
      <a class="topic-pill {{ $selectedTopic === $tid ? 'active' : '' }}"
         href="{{ url('/tg/tasks-part1?topic=' . (int)$tid) }}">
        {{ (int)$tid }}
      </a>
    @endforeach
  </div>

  <div class="sec-label" style="margin-top:14px;">Задание {{ (int)$selectedTopic }}</div>

  <div class="task-list">
    @forelse($zadaniya as $group)
      <details class="spoiler" {{ $loop->first ? 'open' : '' }}>
        <summary>{{ $group['title'] }} <span style="font-size:11px;color:var(--muted);font-weight:400;">({{ count($group['tasks']) }})</span></summary>
        <div class="spoiler-body">
          @foreach($group['tasks'] as $task)
            <div class="task-item">
              @php
                $svg = is_string($task['svg'] ?? null) ? $task['svg'] : '';
                $image = is_string($task['image'] ?? null) ? $task['image'] : '';
              @endphp

              @php
                $svgMarkup = $svg !== '' ? $svg : (\Illuminate\Support\Str::startsWith($image, '<svg') ? $image : '');
                $isWide = (int)$selectedTopic === 11;
              @endphp
              @if($svgMarkup !== '')
                <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; background:#0a1628; padding:8px;{{ $isWide ? ' overflow-x:auto; -webkit-overflow-scrolling:touch;' : '' }}">
                  <div style="{{ $isWide ? 'min-width:600px;' : '' }}">{!! $svgMarkup !!}</div>
                </div>
              @endif

              @if(!empty($task['question']))
                <div class="task-item-text" style="margin-bottom:6px; color:var(--muted); font-size:12px;">{{ $task['question'] }}</div>
              @endif

              @if($task['text'] !== '')
                <div class="task-item-text">{!! nl2br(e($task['text'])) !!}</div>
              @elseif(!empty($task['expression']))
                <div class="task-item-text" style="font-size:15px;">$${{ $task['expression'] }}$$</div>
              @endif

              @if(!empty($task['options']) && is_array($task['options']))
                <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px;">
                  @foreach($task['options'] as $opt)
                    <span style="padding:4px 10px; border:1px solid var(--border); border-radius:8px; font-size:12px; color:var(--muted);">{{ is_array($opt) ? ($opt['label'] ?? $opt['text'] ?? json_encode($opt)) : $opt }}</span>
                  @endforeach
                </div>
              @endif

              @if(!empty($task['answer']))
                <div class="answer-row">
                  <span class="answer-label">Ответ:</span>
                  @if($isPremium)
                    <span class="answer-value">{{ $task['answer'] }}</span>
                  @else
                    <span class="answer-blur">{{ $task['answer'] }}</span>
                    <span class="premium-cta" @click="showPremium = true">Premium</span>
                  @endif
                </div>
              @endif

              @if(!empty($task['id']))
                <div class="task-item-meta">#{{ $task['id'] }}</div>
              @endif
            </div>
          @endforeach
        </div>
      </details>
    @empty
      <div class="task-item">
        <div class="task-item-text">Для этого задания пока нет заданий в статусе production.</div>
      </div>
    @endforelse
  </div>

  {{-- Premium modal --}}
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
