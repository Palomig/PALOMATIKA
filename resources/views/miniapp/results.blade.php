@extends('layouts.miniapp')

@section('title', 'Результаты — palomatika')

@push('styles')
  /* ── SCORE HERO ── */
  .score-hero {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 22px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .score-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 6px;
  }

  .score-nums { display: flex; align-items: baseline; gap: 3px; margin-bottom: 8px; }
  .score-big  { font-family: var(--display); font-size: 54px; line-height: 1; color: var(--text); }
  .score-of   { font-family: var(--display); font-size: 20px; color: var(--muted2); margin-bottom: 4px; }

  .score-message { font-size: 13px; font-weight: 700; color: var(--muted); line-height: 1.45; }

  /* ── STAT ROW ── */
  .stat-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; }

  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 13px 10px;
    text-align: center;
  }

  .stat-val { font-family: var(--display); font-size: 19px; display: block; }
  .stat-val.good { color: var(--green); }
  .stat-val.bad  { color: var(--red); }
  .stat-val.neu  { color: var(--text); }
  .stat-lbl { font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 3px; }

  /* ── TABS ── */
  .tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 4px;
    gap: 4px;
  }

  .tab-btn {
    padding: 10px;
    text-align: center;
    font-size: 13px;
    font-weight: 800;
    color: var(--muted);
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    user-select: none; -webkit-user-select: none;
    border: none; background: none;
    font-family: var(--body);
  }
  .tab-btn.active { background: var(--accent); color: #fff; }

  /* ── ANSWERS LIST ── */
  .answers-list { display: flex; flex-direction: column; gap: 6px; }

  .answer-row {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
  }

  .answer-row-main {
    display: flex;
    align-items: center;
    padding: 11px 14px;
    gap: 11px;
  }

  .ans-num {
    width: 30px; height: 30px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-family: var(--display);
    font-size: 12px;
    flex-shrink: 0;
  }
  .ans-num.correct { background: var(--green-bg); color: var(--green); }
  .ans-num.wrong   { background: var(--red-bg);   color: var(--red); }
  .ans-num.skip    { background: var(--surface2);  color: var(--muted); }

  .ans-body { flex: 1; min-width: 0; }
  .ans-topic {
    font-size: 11px; font-weight: 700; color: var(--muted);
    margin-bottom: 3px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .ans-answers { font-size: 12px; font-weight: 700; display: flex; gap: 8px; flex-wrap: wrap; }
  .your-ans         { color: var(--red); }
  .your-ans.correct { color: var(--green); }
  .correct-ans      { color: var(--green); }
  .skip-ans         { color: var(--muted); }

  .ans-status {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .ans-status.correct { background: var(--green); }
  .ans-status.wrong   { background: var(--red); }
  .ans-status.skip    { background: var(--muted2); }

  /* ── DIVIDER ── */
  .res-divider { height: 1px; background: var(--border); }

  /* ── BATTLE LEADERBOARD ── */
  .battle-board {
    margin-top: 14px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 12px;
  }
  .battle-title {
    font-size: 11px;
    font-weight: 800;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
  }
  .battle-row {
    display: grid;
    grid-template-columns: 32px 1fr auto auto;
    gap: 8px;
    align-items: center;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 9px 10px;
    margin-bottom: 8px;
  }
  .battle-row:last-child { margin-bottom: 0; }
  .battle-rank { text-align:center; font-weight: 900; color: var(--accent); }
  .battle-name { color: var(--text); font-size: 13px; font-weight: 700; }
  .battle-score { color: var(--green); font-size: 12px; font-weight: 800; }
  .battle-time { color: var(--muted); font-size: 12px; font-weight: 700; }
  .battle-me { border-color: rgba(79,142,247,.55); box-shadow: inset 0 0 0 1px rgba(79,142,247,.16); }

  /* ── TUTOR CTA ── */
  .tutor-cta {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    overflow: hidden;
  }

  .tutor-cta-top {
    background: var(--surface2);
    border-bottom: 1px solid var(--border);
    padding: 20px;
  }

  .cta-eyebrow {
    font-size: 10px; font-weight: 800;
    letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--muted); margin-bottom: 8px;
  }

  .cta-title {
    font-family: var(--display);
    font-size: 20px; color: var(--text);
    line-height: 1.2; margin-bottom: 8px;
  }
  .cta-title span { color: var(--green); }

  .cta-sub {
    font-size: 13px; font-weight: 600;
    color: var(--muted); line-height: 1.5;
  }

  .tutor-cta-body { padding: 16px 20px; }

  .tutor-facts { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }

  .tutor-fact {
    display: flex; align-items: flex-start;
    gap: 10px; font-size: 13px; font-weight: 700;
    color: var(--text); line-height: 1.4;
  }

  .fact-icon { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
  .fact-muted { color: var(--muted); font-weight: 600; font-size: 12px; }

  .btn-cta {
    width: 100%;
    background: var(--green);
    color: #0e1a12;
    border: none; border-radius: 12px;
    padding: 16px;
    font-family: var(--body); font-size: 15px; font-weight: 800;
    cursor: pointer; margin-bottom: 8px;
    -webkit-appearance: none;
    transition: opacity 0.15s, transform 0.12s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    text-decoration: none;
  }
  .btn-cta:active { transform: scale(0.98); opacity: 0.9; }

  .btn-cta-secondary {
    width: 100%;
    background: transparent;
    color: var(--muted);
    border: 1px solid var(--border);
    border-radius: 12px; padding: 13px;
    font-family: var(--body); font-size: 13px; font-weight: 700;
    cursor: pointer; -webkit-appearance: none;
    transition: background 0.15s;
    text-decoration: none;
    text-align: center;
    display: block;
  }
  .btn-cta-secondary:active { background: var(--surface2); }

  /* ── PARENT BLOCK ── */
  .parent-block {
    background: var(--surface);
    border: 1px solid var(--border);
    border-left: 3px solid var(--yellow);
    border-radius: var(--r);
    padding: 16px;
  }

  .parent-label {
    font-size: 10px; font-weight: 800;
    letter-spacing: 0.12em; text-transform: uppercase;
    color: var(--yellow); margin-bottom: 8px;
  }

  .parent-text {
    font-size: 13px; font-weight: 600;
    color: var(--muted); line-height: 1.6; margin-bottom: 12px;
  }

  .btn-parent {
    width: 100%;
    background: var(--yellow-bg);
    color: var(--yellow);
    border: 1px solid var(--yellow-bd);
    border-radius: 10px; padding: 12px;
    font-family: var(--body); font-size: 13px; font-weight: 800;
    cursor: pointer; -webkit-appearance: none;
    transition: opacity 0.15s;
    text-decoration: none;
    text-align: center;
    display: block;
  }
  .btn-parent:active { opacity: 0.75; }

  /* ── RETRY ── */
  .btn-retry {
    width: 100%;
    background: var(--accent);
    color: #fff;
    border: none; border-radius: var(--r); padding: 16px;
    font-family: var(--body); font-size: 15px; font-weight: 800;
    cursor: pointer; -webkit-appearance: none;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: opacity 0.15s, transform 0.12s;
    text-decoration: none;
  }
  .btn-retry:active { transform: scale(0.98); }
@endpush

@section('body')
@php
    $pct = $totalTasks > 0 ? round(($correctCount / $totalTasks) * 100) : 0;
    $errorCount = $totalTasks - $correctCount;

    if ($pct >= 90) {
        $message = 'Отличный результат!';
    } elseif ($pct >= 70) {
        $message = 'Хорошая работа!';
    } elseif ($pct >= 50) {
        $message = 'Неплохо, но есть над чем работать';
    } elseif ($pct >= 30) {
        $message = 'Нужно больше практики';
    } else {
        $message = 'Не расстраивайся — начни с разбора ошибок';
    }

    $totalMinutes = intdiv($totalTime, 60);
    $totalSeconds = $totalTime % 60;
    $timeFormatted = sprintf('%02d:%02d', $totalMinutes, $totalSeconds);

    $topicNames = [
        '06' => 'Дроби',
        '07' => 'Числа',
        '08' => 'Корни',
        '09' => 'Уравнения',
        '10' => 'Вероятность',
        '11' => 'Графики',
        '12' => 'Формулы',
        '13' => 'Неравенства',
        '14' => 'Прогрессии',
        '15' => 'Треугольники',
        '16' => 'Окружность',
        '17' => 'Четырёхугольники',
        '18' => 'Клетчатая бумага',
        '19' => 'Высказывания',
    ];

    // Build slot => exam_number and locator maps from variant config
    $variantTasks = is_array($attempt->variant?->config_json['tasks'] ?? null)
        ? $attempt->variant->config_json['tasks'] : [];
    $examNumberMap = [];
    $locatorMap = [];
    foreach ($variantTasks as $vt) {
        $slot = (int) ($vt['slot'] ?? 0);
        $examNum = (int) ($vt['exam_number'] ?? $vt['task_number'] ?? 0);
        if ($slot > 0 && $examNum > 0) {
            $examNumberMap[$slot] = $examNum;
        }
        $taskNum = (int) ($vt['task_number'] ?? 0);
        if ($taskNum > 0) {
            $tid = str_pad((string) ($vt['topic_id'] ?? 'x'), 2, '0', STR_PAD_LEFT);
            $b = $vt['block_number'] ?? 'x';
            $z = $vt['zadanie_number'] ?? 'x';
            $i = $vt['task_id'] ?? $vt['task']['id'] ?? 'x';
            $locatorMap[$taskNum] = "t{$tid}-b{$b}-z{$z}-i{$i}";
        }
    }

    // Build rows data for Alpine
    $rows = [];
    foreach ($scorings as $scoring) {
        $slot = (int) $scoring->task_number;
        $examNum = $examNumberMap[$slot] ?? $slot;
        $tn = str_pad($examNum, 2, '0', STR_PAD_LEFT);
        $studentAnswer = $answers->firstWhere('task_number', $scoring->task_number);
        $answerText = $studentAnswer ? $studentAnswer->current_answer : '';
        $taskDef = $variantTasks[$slot - 1] ?? [];
        $taskInner = is_array($taskDef['task'] ?? null) ? $taskDef['task'] : [];
        $taskOptions = [];
        if (is_array($taskDef['options'] ?? null)) {
            $taskOptions = array_values($taskDef['options']);
        } elseif (is_array($taskInner['options'] ?? null)) {
            $taskOptions = array_values($taskInner['options']);
        }

        $rows[] = [
            'num' => $examNum,
            'topicKey' => $tn,
            'topicName' => $topicNames[$tn] ?? "Задание {$examNum}",
            'locator' => $locatorMap[$examNum] ?? '',
            'yourAnswer' => \App\Support\OptionLabelFormatter::formatAnswer($answerText ?? '', $taskOptions),
            'correctAnswer' => \App\Support\OptionLabelFormatter::formatAnswer($scoring->correct_answer ?? '', $taskOptions),
            'isCorrect' => (bool) $scoring->is_correct,
        ];
    }

    usort($rows, fn($a, $b) => $a['num'] <=> $b['num']);

    // Collect wrong topic names for tutor CTA
    $wrongTopics = collect($rows)->where('isCorrect', false)->pluck('topicName')->unique()->values()->all();

    $submittedDate = $attempt->submitted_at
        ? \Carbon\Carbon::parse($attempt->submitted_at)->format('d.m.Y')
        : now()->format('d.m.Y');
@endphp

<div class="page" x-data="resultsPage()" x-cloak>

  {{-- TOP BAR --}}
  <div class="topbar anim-down">
    <a href="{{ url('/tg/dashboard') }}" class="back-btn">&lsaquo;</a>
    <div>
      <div class="topbar-title">Результаты</div>
    </div>
    <div style="width:36px;"></div>
  </div>

  {{-- STAT ROW --}}
  <div class="stat-row anim-up" style="animation-delay:0.1s">
    <div class="stat-card">
      <span class="stat-val good">{{ $correctCount }}</span>
      <span class="stat-lbl">верно</span>
    </div>
    <div class="stat-card">
      <span class="stat-val bad">{{ $errorCount }}</span>
      <span class="stat-lbl">ошибок</span>
    </div>
    <div class="stat-card">
      <span class="stat-val neu">{{ $timeFormatted }}</span>
      <span class="stat-lbl">время</span>
    </div>
  </div>

  @if(($isBattleVariant ?? false) && !empty($leaderboard ?? []))
    <div class="battle-board anim-up" style="animation-delay:0.13s">
      <div class="battle-title">Таблица участников</div>
      @foreach(($leaderboard ?? []) as $item)
        @php
          $sec = max(0, (int) ($item['time_sec'] ?? 0));
          $t = sprintf('%02d:%02d', intdiv($sec, 60), $sec % 60);
        @endphp
        <div class="battle-row {{ !empty($item['is_me']) ? 'battle-me' : '' }}">
          <div class="battle-rank">{{ $item['rank'] ?? '—' }}</div>
          <div class="battle-name">{{ $item['student_name'] ?? 'Участник' }}</div>
          <div class="battle-score">{{ (int)($item['correct'] ?? 0) }}/{{ (int)($item['total'] ?? 0) }}</div>
          <div class="battle-time">{{ $t }}</div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- TABS --}}
  <div class="tabs anim-up" style="animation-delay:0.15s">
    <button class="tab-btn" :class="{ active: tab === 'all' }" @click="tab = 'all'">Все ответы</button>
    <button class="tab-btn" :class="{ active: tab === 'errors' }" @click="tab = 'errors'">Только ошибки</button>
  </div>

  {{-- ANSWERS LIST --}}
  <div class="answers-list anim-up" style="animation-delay:0.2s">
    <template x-for="row in filteredRows" :key="row.num">
      <div class="answer-row">
        <div class="answer-row-main">
          {{-- Number badge --}}
          <div class="ans-num"
               :class="row.isCorrect ? 'correct' : (row.yourAnswer === '' ? 'skip' : 'wrong')"
               x-text="row.num"></div>

          {{-- Body --}}
          <div class="ans-body">
            <div class="ans-topic">
              <span x-text="row.topicName"></span>
              <template x-if="row.locator">
                <span style="font-size:10px;color:var(--muted);margin-left:6px;font-weight:600;opacity:.7" x-text="row.locator"></span>
              </template>
            </div>
            <div class="ans-answers">
              <template x-if="row.yourAnswer === '' && !row.isCorrect">
                <span class="skip-ans">без ответа</span>
              </template>
              <template x-if="row.yourAnswer !== '' && row.isCorrect">
                <span class="your-ans correct" x-text="'твой: ' + row.yourAnswer"></span>
              </template>
              <template x-if="row.yourAnswer !== '' && !row.isCorrect">
                <span class="your-ans" x-text="'твой: ' + row.yourAnswer"></span>
              </template>
              <template x-if="!row.isCorrect">
                <span class="correct-ans" x-text="'верно: ' + row.correctAnswer"></span>
              </template>
            </div>
          </div>

          {{-- Status dot --}}
          <div class="ans-status"
               :class="row.isCorrect ? 'correct' : (row.yourAnswer === '' ? 'skip' : 'wrong')"></div>
        </div>
      </div>
    </template>
  </div>

  <div class="res-divider anim-up" style="animation-delay:0.25s"></div>

  {{-- TUTOR CTA --}}
  @if($errorCount > 0)
  <div class="tutor-cta anim-up" style="animation-delay:0.3s">
    <div class="tutor-cta-top">
      <div class="cta-eyebrow">Специально для тебя</div>
      <div class="cta-title">Разберём ошибки<br><span>вместе с репетитором</span></div>
      <div class="cta-sub">
        @if(count($wrongTopics) > 0)
          У тебя {{ $errorCount }} {{ $errorCount === 1 ? 'ошибка' : ($errorCount < 5 ? 'ошибки' : 'ошибок') }}
          в {{ implode(', ', array_map('mb_strtolower', $wrongTopics)) }}
          — именно там теряются баллы. Первый урок бесплатно.
        @else
          У тебя {{ $errorCount }} {{ $errorCount === 1 ? 'ошибка' : ($errorCount < 5 ? 'ошибки' : 'ошибок') }}.
          Первый урок бесплатно.
        @endif
      </div>
    </div>
    <div class="tutor-cta-body">
      <div class="tutor-facts">
        <div class="tutor-fact">
          <span class="fact-icon">📍</span>
          <div>г. Чехов, ул. Московская, 87/1<br><span class="fact-muted">занятия в кабинете</span></div>
        </div>
        <div class="tutor-fact">
          <span class="fact-icon">🎓</span>
          <div>Репетиторы с опытом 5+ лет<br><span class="fact-muted">Станислав Олегович и Руслан Романович</span></div>
        </div>
        <div class="tutor-fact">
          <span class="fact-icon">⏱️</span>
          <div>До экзамена ещё есть время<br><span class="fact-muted">успеем закрыть все пробелы</span></div>
        </div>
      </div>
      <a href="{{ url('/tg/tutor') }}" class="btn-cta">Записаться к репетитору</a>
      <a href="https://t.me/Palomig" class="btn-cta-secondary" target="_blank">Написать в Telegram</a>
    </div>
  </div>
  @endif

  {{-- RETRY --}}
  <a href="{{ url('/tg/dashboard') }}" class="btn-retry anim-up" style="animation-delay:0.4s">
    Вернуться на главную
  </a>

</div>
@endsection

@push('scripts')
<script>
  function resultsPage() {
    return {
      tab: 'all',
      rows: @json($rows),

      get filteredRows() {
        if (this.tab === 'errors') {
          return this.rows.filter(r => !r.isCorrect);
        }
        return this.rows;
      }
    };
  }
</script>
@endpush
