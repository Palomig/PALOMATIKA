<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Проверка вопросов — palomatika</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; overflow: hidden; }
body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: #1e2030;
  color: #c9cdd8;
  display: flex; flex-direction: column; height: 100%;
}

/* ── TOP BAR ── */
.top-bar {
  flex-shrink: 0;
  background: #252837;
  border-bottom: 1px solid #2e3248;
  padding: 0 20px;
  height: 52px;
  display: flex; align-items: center; gap: 12px;
}
.top-bar-title {
  font-size: 15px; font-weight: 700; color: #e2e4ee;
  letter-spacing: -0.01em; white-space: nowrap;
}
.top-bar-sep { width: 1px; height: 20px; background: #2e3248; }
.stats { display: flex; gap: 6px; align-items: center; }
.stat {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600;
  padding: 4px 10px; border-radius: 6px;
  background: #2e3248; color: #8892b0;
}
.stat .dot { width: 7px; height: 7px; border-radius: 50%; }
.stat-a .dot { background: #5a9e7a; }
.stat-a .val { color: #7bbf9e; }
.stat-r .dot { background: #a06060; }
.stat-r .val { color: #c07a7a; }
.stat-p .dot { background: #6672a0; }
.spacer { flex: 1; }
.reset-btn {
  padding: 7px 14px; background: transparent;
  border: 1px solid #2e3248; color: #6672a0;
  border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer;
  transition: all 0.15s;
}
.reset-btn:hover { border-color: #a06060; color: #c07a7a; background: rgba(160,96,96,0.08); }
.save-btn {
  padding: 7px 18px;
  background: #4a6cf7; color: #fff; border: none;
  border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer;
  transition: background 0.15s;
}
.save-btn:hover { background: #3a59e0; }

/* ── NOTIFICATION BARS ── */
.notify-bar {
  flex-shrink: 0; padding: 8px 20px;
  font-size: 12px; font-weight: 600; text-align: center;
  border-bottom: 1px solid;
}
.notify-stale { background: rgba(180,130,60,0.1); border-color: rgba(180,130,60,0.2); color: #b8924a; }
.notify-saved { background: rgba(90,158,122,0.1); border-color: rgba(90,158,122,0.2); color: #5a9e7a; }

/* ── BOARD WRAPPER ── */
.board-wrap {
  flex: 1; min-height: 0;
  overflow-x: auto; overflow-y: hidden;
  padding-bottom: 6px;
}
/* Thick scrollbar */
.board-wrap::-webkit-scrollbar { height: 14px; }
.board-wrap::-webkit-scrollbar-track { background: #1a1c2a; }
.board-wrap::-webkit-scrollbar-thumb {
  background: #3a3f58; border-radius: 7px;
  border: 3px solid #1a1c2a;
}
.board-wrap::-webkit-scrollbar-thumb:hover { background: #4a5070; }

/* ── BOARD ── */
.board {
  display: flex; gap: 10px;
  padding: 14px 18px 4px;
  height: 100%; align-items: flex-start;
  width: max-content;
}

/* ── COLUMN ── */
.col {
  width: 272px; flex-shrink: 0;
  background: #252837;
  border-radius: 12px;
  display: flex; flex-direction: column;
  max-height: calc(100vh - 52px - 14px - 28px);
  overflow: hidden;
}
.col-header {
  padding: 12px 14px 10px;
  border-bottom: 1px solid #2e3248;
  flex-shrink: 0;
}
.col-header-top {
  display: flex; align-items: center; gap: 8px; margin-bottom: 5px;
}
.col-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.col-title { font-size: 12px; font-weight: 700; color: #e2e4ee; }
.col-num { font-size: 11px; color: #6672a0; margin-left: auto; font-weight: 600; }
.col-sub { font-size: 11px; color: #6672a0; padding-left: 18px; margin-bottom: 8px; }
.col-progress { height: 3px; background: #2e3248; border-radius: 2px; }
.col-progress-fill { height: 3px; border-radius: 2px; transition: width 0.35s ease; }

/* Column accent colors (muted, Monday-style) */
@php $dotColors = [6=>'#7aa4d0',7=>'#a07ad0',8=>'#d07aa0',9=>'#d0a07a',10=>'#7ad0a0',11=>'#d0c47a',12=>'#7ab8d0',13=>'#c47a7a',14=>'#7ad4b8',15=>'#b07ad0',16=>'#d09a7a',17=>'#7ab07a',18=>'#d07ab0',19=>'#90a0c0']; @endphp
.col[data-topic="6"]  .col-dot, .col[data-topic="6"]  .col-progress-fill { background: #7aa4d0; }
.col[data-topic="7"]  .col-dot, .col[data-topic="7"]  .col-progress-fill { background: #a07ad0; }
.col[data-topic="8"]  .col-dot, .col[data-topic="8"]  .col-progress-fill { background: #d07aa0; }
.col[data-topic="9"]  .col-dot, .col[data-topic="9"]  .col-progress-fill { background: #d0a07a; }
.col[data-topic="10"] .col-dot, .col[data-topic="10"] .col-progress-fill { background: #7ad0a0; }
.col[data-topic="11"] .col-dot, .col[data-topic="11"] .col-progress-fill { background: #d0c47a; }
.col[data-topic="12"] .col-dot, .col[data-topic="12"] .col-progress-fill { background: #7ab8d0; }
.col[data-topic="13"] .col-dot, .col[data-topic="13"] .col-progress-fill { background: #c47a7a; }
.col[data-topic="14"] .col-dot, .col[data-topic="14"] .col-progress-fill { background: #7ad4b8; }
.col[data-topic="15"] .col-dot, .col[data-topic="15"] .col-progress-fill { background: #b07ad0; }
.col[data-topic="16"] .col-dot, .col[data-topic="16"] .col-progress-fill { background: #d09a7a; }
.col[data-topic="17"] .col-dot, .col[data-topic="17"] .col-progress-fill { background: #7ab07a; }
.col[data-topic="18"] .col-dot, .col[data-topic="18"] .col-progress-fill { background: #d07ab0; }
.col[data-topic="19"] .col-dot, .col[data-topic="19"] .col-progress-fill { background: #90a0c0; }

/* ── CARDS SCROLL AREA ── */
.col-cards {
  overflow-y: auto; flex: 1; padding: 10px 10px 10px;
  display: flex; flex-direction: column; gap: 8px;
}
.col-cards::-webkit-scrollbar { width: 4px; }
.col-cards::-webkit-scrollbar-track { background: transparent; }
.col-cards::-webkit-scrollbar-thumb { background: #2e3248; border-radius: 2px; }

/* ── CARD ── */
.q-card {
  background: #1e2030;
  border: 1px solid #2e3248;
  border-radius: 10px;
  padding: 13px;
  transition: border-color 0.15s, opacity 0.15s;
}
.q-card.approved {
  border-color: #3a6650;
  background: #1e2830;
}
.q-card.rejected {
  border-color: #3a2828;
  background: #201e1e;
  opacity: 0.6;
}

.q-num {
  font-size: 10px; font-weight: 700; color: #4a5070;
  margin-bottom: 6px; letter-spacing: 0.04em;
}
.q-text {
  font-size: 14px; color: #d4d6e4; font-weight: 600;
  line-height: 1.5; margin-bottom: 10px;
}

.choices { display: flex; flex-direction: column; gap: 3px; margin-bottom: 12px; }
.choice {
  font-size: 12px; color: #5a6080;
  padding: 4px 7px; border-radius: 5px; line-height: 1.35;
}
.choice.correct {
  color: #7bbf9e; font-weight: 700;
  background: rgba(90,158,122,0.1);
}

.actions { display: flex; gap: 6px; }
.btn-a, .btn-r {
  flex: 1; padding: 7px 0;
  border-radius: 7px; font-size: 12px; font-weight: 600;
  border: 1px solid; cursor: pointer;
  transition: all 0.12s; background: transparent;
}
.btn-a { border-color: #3a5048; color: #7bbf9e; }
.btn-a:hover { background: rgba(90,158,122,0.12); border-color: #5a9e7a; }
.btn-a.on { background: rgba(90,158,122,0.18); border-color: #5a9e7a; color: #a0d8b8; }
.btn-r { border-color: #4a3838; color: #c07a7a; }
.btn-r:hover { background: rgba(160,96,96,0.1); border-color: #a06060; }
.btn-r.on { background: rgba(160,96,96,0.16); border-color: #a06060; color: #d89898; }
</style>
</head>
<body>

<div class="top-bar">
  <div class="top-bar-title">Проверка вопросов</div>
  <div class="top-bar-sep"></div>
  <div class="stats">
    <span class="stat stat-a"><span class="dot"></span> <span class="val" id="cnt-a">{{ $approvedCount }}</span> одобрено</span>
    <span class="stat stat-r"><span class="dot"></span> <span class="val" id="cnt-r">{{ $rejectedCount }}</span> отклонено</span>
    <span class="stat stat-p"><span class="dot"></span> <span id="cnt-p">{{ $totalCount - $approvedCount - $rejectedCount }}</span> не проверено</span>
  </div>
  <div class="spacer"></div>
  <form method="POST" action="{{ route('demo.diagnostic.review.save') }}" style="margin:0">
    @csrf <input type="hidden" name="reset" value="1">
    <button type="submit" class="reset-btn">Сбросить всё</button>
  </form>
  <form id="save-form" method="POST" action="{{ route('demo.diagnostic.review.save') }}" style="margin:0">
    @csrf
    <div id="hidden-inputs"></div>
    <button type="submit" class="save-btn">Сохранить</button>
  </form>
</div>

@if($isStale)
<div class="notify-bar notify-stale">Банк вопросов изменился — старые оценки сброшены. Пройдите проверку заново.</div>
@endif
@if(session('saved'))
<div class="notify-bar notify-saved">Сохранено — {{ $approvedCount }} вопросов одобрено</div>
@endif

<div class="board-wrap">
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
<div class="col" id="col-{{ $topicId }}" data-topic="{{ $topicId }}">
  <div class="col-header">
    <div class="col-header-top">
      <div class="col-dot"></div>
      <div class="col-title">{{ $topicName }}</div>
      <div class="col-num">№{{ $topicId }}</div>
    </div>
    <div class="col-sub">{{ $approvedInTopic }} из {{ count($cards) }} одобрено</div>
    <div class="col-progress">
      <div class="col-progress-fill" id="prog-{{ $topicId }}"
           style="width: {{ $approvedInTopic * 25 }}%"></div>
    </div>
  </div>

  <div class="col-cards">
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
          onclick="toggle({{ $card['id'] }}, 'approved')">✓ Подходит</button>
        <button type="button" class="btn-r {{ $card['status'] === 'rejected' ? 'on' : '' }}"
          onclick="toggle({{ $card['id'] }}, 'rejected')">✗ Нет</button>
      </div>
    </div>
    @endforeach
  </div>
</div>
@endforeach

</div>
</div>

<script>
const s = {};
@foreach($items as $item)
s[{{ $item['id'] }}] = { status: '{{ $item['status'] }}', topic: {{ $item['topic_id'] }} };
@endforeach

function toggle(id, action) {
  s[id].status = s[id].status === action ? 'pending' : action;
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

  const topic = s[id].topic;
  const topicCards = Object.values(s).filter(x => x.topic === topic);
  const done = topicCards.filter(x => x.status === 'approved').length;
  const total = topicCards.length;
  document.getElementById('prog-' + topic).style.width = (done / total * 100) + '%';
  document.querySelector('#col-' + topic + ' .col-sub').textContent = done + ' из ' + total + ' одобрено';
}

function updateStats() {
  const vals = Object.values(s);
  document.getElementById('cnt-a').textContent = vals.filter(x => x.status === 'approved').length;
  document.getElementById('cnt-r').textContent = vals.filter(x => x.status === 'rejected').length;
  document.getElementById('cnt-p').textContent = vals.filter(x => x.status === 'pending').length;
}

function buildInputs() {
  let html = ''; let ai = 0, ri = 0;
  for (const [id, d] of Object.entries(s)) {
    if (d.status === 'approved') html += `<input type="hidden" name="approved[${ai++}]" value="${id}">`;
    else if (d.status === 'rejected') html += `<input type="hidden" name="rejected[${ri++}]" value="${id}">`;
  }
  document.getElementById('hidden-inputs').innerHTML = html;
}
buildInputs();
</script>
</body>
</html>
