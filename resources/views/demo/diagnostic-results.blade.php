<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Результаты диагностики — palomatika</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0e1117; color: #e8eaf0; min-height: 100vh; }

  .demo-banner { background: linear-gradient(90deg, #f59e0b, #f97316); color: #fff; text-align: center; padding: 8px 16px; font-size: 13px; font-weight: 700; letter-spacing: 0.05em; }
  .wrap { max-width: 600px; margin: 0 auto; padding: 16px; }

  .score-card { background: #1a1d27; border: 1px solid #2a2d3a; border-radius: 16px; padding: 24px; text-align: center; margin-bottom: 20px; }
  .score-num { font-size: 52px; font-weight: 900; line-height: 1; }
  .score-num.great { color: #22c55e; }
  .score-num.ok { color: #f59e0b; }
  .score-num.poor { color: #ef4444; }
  .score-sub { font-size: 15px; color: #8b92a5; margin-top: 8px; }
  .score-pct { font-size: 22px; font-weight: 800; margin-top: 4px; }

  .retry-btn { display: block; width: 100%; padding: 14px; background: #4f8ef7; color: #fff; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; margin-bottom: 20px; }

  .results-title { font-size: 13px; font-weight: 800; color: #8b92a5; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; }

  .result-row { background: #1a1d27; border: 1px solid #2a2d3a; border-radius: 12px; padding: 14px 16px; margin-bottom: 8px; }
  .result-row.correct { border-left: 3px solid #22c55e; }
  .result-row.wrong { border-left: 3px solid #ef4444; }
  .result-meta { font-size: 10px; font-weight: 800; color: #8b92a5; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
  .result-q { font-size: 13px; color: #e8eaf0; font-weight: 600; margin-bottom: 10px; line-height: 1.5; }
  .result-choices { display: flex; flex-direction: column; gap: 4px; }
  .result-choice { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #8b92a5; padding: 5px 8px; border-radius: 7px; }
  .result-choice.is-correct { background: rgba(34,197,94,0.12); color: #22c55e; font-weight: 700; }
  .result-choice.is-wrong { background: rgba(239,68,68,0.1); color: #ef4444; font-weight: 700; }
  .result-choice.just-correct { background: rgba(34,197,94,0.12); color: #22c55e; font-weight: 700; }
  .choice-icon { font-size: 14px; width: 18px; text-align: center; flex-shrink: 0; }
</style>
</head>
<body>

<div class="demo-banner">🔍 ДЕМО-РЕЖИМ — результаты не сохраняются</div>

<div class="wrap">

  <div class="score-card">
    <div class="score-num {{ $percent >= 70 ? 'great' : ($percent >= 40 ? 'ok' : 'poor') }}">
      {{ $score }}/{{ $total }}
    </div>
    <div class="score-pct">{{ $percent }}%</div>
    <div class="score-sub">
      @if($percent >= 70) Отличный результат! 🎉
      @elseif($percent >= 40) Есть над чем поработать
      @else Требуется серьёзная подготовка
      @endif
    </div>
  </div>

  <a href="{{ route('demo.diagnostic') }}" class="retry-btn">↺ Пройти ещё раз</a>

  <div class="results-title">Разбор ответов</div>

  @foreach($results as $r)
  <div class="result-row {{ $r['is_correct'] ? 'correct' : 'wrong' }}">
    <div class="result-meta">{{ $r['category'] }} · {{ $r['skill_name'] }}</div>
    <div class="result-q">{{ $r['question'] }}</div>
    <div class="result-choices">
      @foreach($r['choices'] as $ci => $choice)
        @php
          $isCorrect = (string)$ci === (string)$r['correct'];
          $isUserWrong = (string)$ci === (string)$r['user_choice'] && !$r['is_correct'];
          $letters = ['А','Б','В','Г'];
        @endphp
        <div class="result-choice {{ $isCorrect ? 'is-correct' : ($isUserWrong ? 'is-wrong' : '') }}">
          <span class="choice-icon">
            @if($isCorrect) ✓
            @elseif($isUserWrong) ✗
            @else &nbsp;
            @endif
          </span>
          <span>{{ $letters[$ci] }}. {{ $choice }}</span>
        </div>
      @endforeach
    </div>
  </div>
  @endforeach

</div>
</body>
</html>
