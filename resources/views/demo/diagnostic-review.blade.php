<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Проверка вопросов — palomatika</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; overflow: hidden; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0b0d14; color: #e8eaf0; display: flex; flex-direction: column; }

/* ── TOP BAR ── */
.top-bar {
  flex-shrink: 0;
  background: #13161f; border-bottom: 1px solid #22253a;
  padding: 10px 20px; display: flex; align-items: center; gap: 16px;
}
.top-bar-title { font-size: 14px; font-weight: 800; color: #e8eaf0; white-space: nowrap; }
.stats { display: flex; gap: 10px; align-items: center; margin-left: auto; }
.stat { font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
.stat-a { background: rgba(34,197,94,0.15); color: #22c55e; }
.stat-r { background: rgba(239,68,68,0.12); color: #ef4444; }
.stat-p { background: rgba(79,142,247,0.12); color: #6b8fff; }
.save-btn {
  padding: 7px 18px; background: #4f8ef7; color: #fff; border: none;
  border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; white-space: nowrap;
}
.save-btn:hover { background: #3a7ae0; }
.reset-btn {
  padding: 6px 12px; background: transparent; border: 1px solid #2a2d3a;
  color: #8b92a5; border-radius: 8px; font-size: 12px; cursor: pointer; white-space: nowrap;
}
.reset-btn:hover { border-color: #ef4444; color: #ef4444; }

.stale-bar {
  flex-shrink: 0;
  background: rgba(245,158,11,0.1); border-bottom: 1px solid rgba(245,158,11,0.25);
  padding: 8px 20px; font-size: 12px; color: #f59e0b; font-weight: 600; text-align: center;
}
.saved-bar {
  flex-shrink: 0;
  background: rgba(34,197,94,0.1); border-bottom: 1px solid rgba(34,197,94,0.2);
  padding: 8px 20px; font-size: 12px; color: #22c55e; font-weight: 700; text-align: center;
}

/* ── BOARD ── */
.board {
  flex: 1; overflow-x: auto; overflow-y: hidden;
  display: flex; gap: 10px; padding: 12px 16px 16px;
  align-items: flex-start;
}
.board::-webkit-scrollbar { height: 6px; }
.board::-webkit-scrollbar-track { background: #0b0d14; }
.board::-webkit-scrollbar-thumb { background: #22253a; border-radius: 3px; }

/* ── COLUMN ── */
.col {
  flex-shrink: 0; width: 230px;
  display: flex; flex-direction: column; gap: 8px;
}
.col-header {
  padding: 8px 10px; background: #13161f; border: 1px solid #22253a;
  border-radius: 10px; position: sticky; top: 0;
}
.col-title { font-size: 11px; font-weight: 800; color: #4f8ef7; text-transform: uppercase; letter-spacing: 0.08em; }
.col-sub { font-size: 10px; color: #8b92a5; margin-top: 3px; }
.col-progress { margin-top: 6px; height: 3px; background: #22253a; border-radius: 2px; }
.col-progress-fill { height: 3px; background: #22c55e; border-radius: 2px; transition: width 0.3s; }

/* ── CARD ── */
.q-card {
  background: #13161f; border: 1px solid #22253a; border-radius: 10px;
  padding: 11px; cursor: default; transition: border-color 0.15s, opacity 0.15s;
  position: relative;
}
.q-card.approved { border-color: #22c55e; background: rgba(34,197,94,0.05); }
.q-card.rejected { border-color: #3a1f1f; opacity: 0.5; }

.q-num { font-size: 9px; font-weight: 800; color: #3a4060; margin-bottom: 5px; letter-spacing: 0.06em; }
.q-text { font-size: 13px; color: #c8cfe0; font-weight: 600; line-height: 1.45; margin-bottom: 8px; }

.choices { display: flex; flex-direction: column; gap: 3px; margin-bottom: 9px; }
.choice { font-size: 11px; color: #5a6080; padding: 3px 6px; border-radius: 4px; line-height: 1.3; }
.choice.correct { background: rgba(34,197,94,0.1); color: #4ade80; font-weight: 700; }

.actions { display: flex; gap: 5px; }
.btn-a, .btn-r {
  flex: 1; padding: 6px 0; border-radius: 7px; font-size: 11px; font-weight: 700;
  border: 1.5px solid; cursor: pointer; transition: all 0.12s; background: transparent;
}
.btn-a { border-color: rgba(34,197,94,0.3); color: #4ade80; }
.btn-a:hover, .btn-a.on { background: rgba(34,197,94,0.18); border-color: #22c55e; }
.btn-r { border-color: rgba(239,68,68,0.25); color: #f87171; }
.btn-r:hover, .btn-r.on { background: rgba(239,68,68,0.15); border-color: #ef4444; }
</style>
</head>
<body>

<div class="top-bar">
  <div class="top-bar-title">Проверка вопросов</div>
  <div class="stats">
    <span class="stat stat-a" id="stat-a">✓ <span id="cnt-a">{{ $approvedCount }}</span></span>
    <span class="stat stat-r" id="stat-r">✗ <span id="cnt-r">{{ $rejectedCount }}</span></span>
    <span class="stat stat-p">? <span id="cnt-p">{{ $totalCount - $approvedCount - $rejectedCount }}</span></span>
  </div>
  <form id="reset-form" method="POST" action="{{ route('demo.diagnostic.review.save') }}" style="margin:0">
    @csrf <input type="hidden" name="reset" value="1">
    <button type="submit" class="reset-btn">Сбросить</button>
  </form>
  <form id="save-form" method="POST" action="{{ route('demo.diagnostic.review.save') }}" style="margin:0">
    @csrf
    <div id="hidden-inputs"></div>
    <button type="submit" class="save-btn">Сохранить</button>
  </form>
</div>

@if($isStale)
<div class="stale-bar">⚠ Банк вопросов изменился — старые оценки сброшены. Проверьте заново.</div>
@endif
@if(session('saved'))
<div class="saved-bar">✓ Сохранено — {{ $approvedCount }} одобрено</div>
@endif

<div class="board">

@php
  $byTopic = [];
  foreach ($items as $item) {
      $byTopic[$item['topic_id']][] = $item;
  }
  $letters = ['А','Б','В','Г'];
@endphp

@foreach($byTopic as $topicId => $cards)
@php
  $topicName = $cards[0]['topic_name'];
  $approvedInTopic = count(array_filter($cards, fn($c) => $c['status'] === 'approved'));
@endphp
<div class="col" id="col-{{ $topicId }}">
  <div class="col-header">
    <div class="col-title">№{{ $topicId }}</div>
    <div class="col-sub">{{ $topicName }}</div>
    <div class="col-progress">
      <div class="col-progress-fill" id="prog-{{ $topicId }}"
           style="width: {{ $approvedInTopic * 25 }}%"></div>
    </div>
  </div>

  @foreach($cards as $card)
  <div class="q-card {{ $card['status'] }}" id="card-{{ $card['id'] }}" data-status="{{ $card['status'] }}" data-topic="{{ $topicId }}">
    <div class="q-num">{{ $topicId }}.{{ $loop->iteration }}</div>
    <div class="q-text">{{ $card['question'] }}</div>
    <div class="choices">
      @foreach($card['choices'] as $ci => $choice)
      <div class="choice {{ $ci === $card['correct'] ? 'correct' : '' }}">
        {{ $letters[$ci] }}. {{ $choice }}
      </div>
      @endforeach
    </div>
    <div class="actions">
      <button type="button" class="btn-a {{ $card['status'] === 'approved' ? 'on' : '' }}"
        onclick="toggle({{ $card['id'] }}, 'approved')">✓ Да</button>
      <button type="button" class="btn-r {{ $card['status'] === 'rejected' ? 'on' : '' }}"
        onclick="toggle({{ $card['id'] }}, 'rejected')">✗ Нет</button>
    </div>
  </div>
  @endforeach
</div>
@endforeach

</div>

<script>
const s = {};
@foreach($items as $item)
s[{{ $item['id'] }}] = { status: '{{ $item['status'] }}', topic: {{ $item['topic_id'] }} };
@endforeach

function toggle(id, action) {
  const prev = s[id].status;
  s[id].status = prev === action ? 'pending' : action;
  render(id);
  updateStats();
  buildInputs();
}

function render(id) {
  const card = document.getElementById('card-' + id);
  const st = s[id].status;
  card.className = 'q-card ' + st;
  card.dataset.status = st;
  card.querySelector('.btn-a').classList.toggle('on', st === 'approved');
  card.querySelector('.btn-r').classList.toggle('on', st === 'rejected');

  // update column progress bar
  const topic = s[id].topic;
  const cards = Object.values(s).filter(x => x.topic === topic);
  const done = cards.filter(x => x.status === 'approved').length;
  document.getElementById('prog-' + topic).style.width = (done * 25) + '%';
}

function updateStats() {
  const vals = Object.values(s);
  document.getElementById('cnt-a').textContent = vals.filter(x => x.status === 'approved').length;
  document.getElementById('cnt-r').textContent = vals.filter(x => x.status === 'rejected').length;
  document.getElementById('cnt-p').textContent = vals.filter(x => x.status === 'pending').length;
}

function buildInputs() {
  const c = document.getElementById('hidden-inputs');
  c.innerHTML = '';
  let ai = 0, ri = 0;
  for (const [id, d] of Object.entries(s)) {
    if (d.status === 'approved') {
      c.innerHTML += `<input type="hidden" name="approved[${ai++}]" value="${id}">`;
    } else if (d.status === 'rejected') {
      c.innerHTML += `<input type="hidden" name="rejected[${ri++}]" value="${id}">`;
    }
  }
}
buildInputs();
</script>
</body>
</html>
