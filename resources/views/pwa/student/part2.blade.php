@extends('layouts.pwa')
@section('title', '2я часть ОГЭ — palomatika')

@push('katex')
@include('partials.head-katex')
@endpush

@push('styles')
  .topics-row {
    display: flex; gap: 8px; overflow-x: auto; padding-bottom: 2px;
    opacity: 0; animation: fadeUp 0.3s ease 0.08s forwards;
  }
  .topic-chip {
    display: flex; align-items: center; gap: 6px;
    min-width: max-content;
    padding: 8px 14px; border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text); text-decoration: none;
    font-family: var(--display); font-size: 13px;
    transition: all .15s ease;
  }
  .topic-chip.active {
    border-color: var(--purple);
    background: var(--purple);
    color: #fff;
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--purple) 28%, transparent);
  }
  .topic-chip.active .topic-chip-num { color: #fff; }
  .topic-chip.disabled {
    opacity: 0.4; cursor: default; pointer-events: none;
  }
  .topic-chip-icon { font-size: 16px; }
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
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    letter-spacing: .04em;
    min-width: 18px;
    text-align: left;
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
    font-family: 'Inter', system-ui, sans-serif;
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
  .page-header .header-title { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
@endpush

@section('body')
<div class="page task-render-scope" x-data="taskBrowser()">
  <div class="page-header">
    <a href="{{ route('pwa.student.dashboard') }}" class="back-btn">‹</a>
    <div class="header-title">
      <div class="sec-label">2я часть ОГЭ</div>
      <div class="sec-label">задания 20–25</div>
    </div>
  </div>

  <div class="sec-label">Выбери тему</div>
  <div class="topics-row">
    @foreach($topicsMeta as $tid => $meta)
      <a class="topic-chip {{ $selectedTopic === $tid ? 'active' : '' }} {{ !in_array($tid, $topics) ? 'disabled' : '' }}"
         href="{{ in_array($tid, $topics) ? url('/part2?topic=' . $tid) : '#' }}">
        <span class="topic-chip-icon">{{ $meta['icon'] }}</span>
        <span class="topic-chip-num">{{ $tid }}</span>
      </a>
    @endforeach
    {{-- Show coming soon topics --}}
    @foreach([
      '22' => ['title' => 'Графики', 'icon' => '📈'],
      '23' => ['title' => 'Геометрия (выч.)', 'icon' => '📐'],
      '24' => ['title' => 'Геометрия (док.)', 'icon' => '✏️'],
      '25' => ['title' => 'Геометрия (пов.)', 'icon' => '🔺'],
    ] as $tid => $meta)
      @if(!isset($topicsMeta[$tid]))
        <span class="topic-chip disabled">
          <span class="topic-chip-icon">{{ $meta['icon'] }}</span>
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
    @forelse($zadaniya as $group)
      <details class="spoiler">
        <summary>
          <span class="spoiler-num">{{ str_pad((string)($group['number'] ?? $loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
          <span class="spoiler-body-text">
            <span class="spoiler-title">{{ $group['title'] }}</span>
            @if(!empty($group['subtitle']))
              <span class="spoiler-subtitle">{{ $group['subtitle'] }}</span>
            @endif
          </span>
          <span class="spoiler-chevron">›</span>
        </summary>
        <div class="spoiler-body">
          @if($group['hint'])
            <div class="hint-box">{{ $group['hint'] }}</div>
          @endif
          @foreach($group['tasks'] as $task)
            <div class="task-item">
              @if(!empty($task['image']))
                <img src="{{ asset('images/tasks/' . $selectedTopic . '/' . ltrim($task['image'], '/')) }}"
                     alt="" style="display:block;max-width:100%;height:auto;margin-bottom:10px;border:1px solid var(--border);border-radius:10px;background:#fff;padding:4px;" loading="lazy">
              @endif
              <div class="task-item-text">{{ $task['text'] }}</div>
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
</script>
@endpush
