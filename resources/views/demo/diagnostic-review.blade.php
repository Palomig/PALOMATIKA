<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Проверка вопросов диагностики — palomatika</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0e1117; color: #e8eaf0; min-height: 100vh; }

  .top-bar { background: #1a1d27; border-bottom: 1px solid #2a2d3a; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10; }
  .top-bar-title { font-size: 15px; font-weight: 800; }
  .top-bar-stats { font-size: 12px; color: #8b92a5; display: flex; gap: 14px; align-items: center; }
  .stat-approved { color: #22c55e; font-weight: 700; }
  .stat-rejected { color: #ef4444; font-weight: 700; }
  .reset-btn-top { background: transparent; border: 1px solid #3a3d4a; color: #8b92a5; padding: 4px 10px; border-radius: 6px; font-size: 11px; cursor: pointer; }
  .reset-btn-top:hover { border-color: #ef4444; color: #ef4444; }

  .save-bar { background: #1a1d27; border-top: 1px solid #2a2d3a; padding: 12px 20px; position: sticky; bottom: 0; z-index: 10; }
  .save-btn { display: block; width: 100%; max-width: 480px; margin: 0 auto; padding: 14px; background: #4f8ef7; color: #fff; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; }
  .save-btn:hover { background: #3a7ae0; }

  .wrap { max-width: 700px; margin: 0 auto; padding: 16px 16px 80px; }

  .banner { border-radius: 10px; padding: 10px 16px; font-size: 13px; font-weight: 700; text-align: center; margin-bottom: 16px; }
  .banner.saved { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #22c55e; }
  .banner.stale { background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.3); color: #f59e0b; }

  .filter-bar { display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; }
  .filter-btn { padding: 6px 14px; border-radius: 20px; border: 1.5px solid #2a2d3a; background: transparent; color: #8b92a5; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.15s; }
  .filter-btn.active { border-color: #4f8ef7; background: rgba(79,142,247,0.12); color: #4f8ef7; }
  .filter-btn.approved-filter.active { border-color: #22c55e; background: rgba(34,197,94,0.12); color: #22c55e; }
  .filter-btn.rejected-filter.active { border-color: #ef4444; background: rgba(239,68,68,0.1); color: #ef4444; }

  .topic-header { font-size: 11px; font-weight: 800; color: #8b92a5; text-transform: uppercase; letter-spacing: 0.1em; padding: 16px 0 8px; border-top: 1px solid #2a2d3a; margin-top: 8px; }
  .topic-header:first-of-type { border-top: none; margin-top: 0; }

  .q-card { background: #1a1d27; border: 1px solid #2a2d3a; border-radius: 14px; padding: 16px; margin-bottom: 10px; transition: border-color 0.15s; }
  .q-card.approved { border-left: 3px solid #22c55e; }
  .q-card.rejected { border-left: 3px solid #ef4444; opacity: 0.55; }

  .q-text { font-size: 15px; color: #e8eaf0; font-weight: 700; line-height: 1.5; margin-bottom: 12px; }
  .q-choices { display: flex; flex-direction: column; gap: 4px; margin-bottom: 14px; }
  .q-choice { font-size: 12px; color: #8b92a5; padding: 5px 8px; border-radius: 6px; }
  .q-choice.correct { background: rgba(34,197,94,0.12); color: #22c55e; font-weight: 700; }

  .q-actions { display: flex; gap: 8px; }
  .btn-approve, .btn-reject {
    flex: 1; padding: 9px; border-radius: 9px; font-size: 13px; font-weight: 700;
    border: 1.5px solid; cursor: pointer; transition: all 0.15s;
  }
  .btn-approve { border-color: #22c55e; color: #22c55e; background: transparent; }
  .btn-approve.active { background: rgba(34,197,94,0.2); }
  .btn-reject { border-color: #ef4444; color: #ef4444; background: transparent; }
  .btn-reject.active { background: rgba(239,68,68,0.15); }
</style>
</head>
<body>

<div class="top-bar">
  <div class="top-bar-title">Проверка вопросов · {{ $totalCount }}</div>
  <div class="top-bar-stats">
    <span class="stat-approved" id="stat-approved">✓ {{ $approvedCount }}</span>
    <span class="stat-rejected" id="stat-rejected">✗ {{ $rejectedCount }}</span>
    <form method="POST" action="{{ route('demo.diagnostic.review.save') }}" style="margin:0">
      @csrf
      <input type="hidden" name="reset" value="1">
      <button type="submit" class="reset-btn-top">Сбросить всё</button>
    </form>
  </div>
</div>

<div class="wrap">

  @if($isStale)
  <div class="banner stale">⚠ Банк вопросов изменился — старые оценки сброшены. Проверьте заново.</div>
  @endif

  @if(session('saved'))
  <div class="banner saved">✓ Сохранено — {{ $approvedCount }} вопросов одобрено</div>
  @endif

  <div class="filter-bar">
    <button class="filter-btn active" onclick="filterCards('all', this)">Все ({{ $totalCount }})</button>
    <button class="filter-btn approved-filter" onclick="filterCards('approved', this)">Одобрены</button>
    <button class="filter-btn rejected-filter" onclick="filterCards('rejected', this)">Отклонены</button>
    <button class="filter-btn" onclick="filterCards('pending', this)">Не проверены</button>
  </div>

  <form id="review-form" method="POST" action="{{ route('demo.diagnostic.review.save') }}">
    @csrf

    @php $prevTopic = null; @endphp
    @foreach($items as $item)
      @if($item['is_new_topic'])
      <div class="topic-header" data-topic-header="{{ $item['topic_id'] }}">
        Задание {{ $item['topic_id'] }} — {{ $item['topic_name'] }}
      </div>
      @endif

      @php $letters = ['А','Б','В','Г']; @endphp
      <div class="q-card {{ $item['status'] }}" id="card-{{ $item['id'] }}" data-status="{{ $item['status'] }}" data-topic="{{ $item['topic_id'] }}">
        <div class="q-text">{{ $item['question'] }}</div>
        <div class="q-choices">
          @foreach($item['choices'] as $ci => $choice)
          <div class="q-choice {{ $ci === $item['correct'] ? 'correct' : '' }}">
            {{ $letters[$ci] }}. {{ $choice }}{{ $ci === $item['correct'] ? ' ✓' : '' }}
          </div>
          @endforeach
        </div>
        <div class="q-actions">
          <button type="button" class="btn-approve {{ $item['status'] === 'approved' ? 'active' : '' }}"
            onclick="setStatus({{ $item['id'] }}, 'approved', this)">✓ Подходит</button>
          <button type="button" class="btn-reject {{ $item['status'] === 'rejected' ? 'active' : '' }}"
            onclick="setStatus({{ $item['id'] }}, 'rejected', this)">✗ Не подходит</button>
        </div>
      </div>
    @endforeach

    <div id="hidden-inputs"></div>
  </form>

</div>

<div class="save-bar">
  <button class="save-btn" onclick="document.getElementById('review-form').submit()">Сохранить изменения</button>
</div>

<script>
const statuses = {};

@foreach($items as $item)
statuses[{{ $item['id'] }}] = '{{ $item['status'] }}';
@endforeach

function setStatus(id, status, btn) {
  const card = document.getElementById('card-' + id);
  if (statuses[id] === status) {
    statuses[id] = 'pending';
    card.className = 'q-card pending';
    card.dataset.status = 'pending';
    btn.classList.remove('active');
  } else {
    statuses[id] = status;
    card.className = 'q-card ' + status;
    card.dataset.status = status;
    card.querySelectorAll('.btn-approve, .btn-reject').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }
  updateStats();
  buildHiddenInputs();
}

function updateStats() {
  const a = Object.values(statuses).filter(s => s === 'approved').length;
  const r = Object.values(statuses).filter(s => s === 'rejected').length;
  document.getElementById('stat-approved').textContent = '✓ ' + a;
  document.getElementById('stat-rejected').textContent = '✗ ' + r;
}

function buildHiddenInputs() {
  const c = document.getElementById('hidden-inputs');
  c.innerHTML = '';
  let ai = 0, ri = 0;
  for (const [id, s] of Object.entries(statuses)) {
    if (s === 'approved') {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'approved[' + (ai++) + ']'; inp.value = id;
      c.appendChild(inp);
    } else if (s === 'rejected') {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'rejected[' + (ri++) + ']'; inp.value = id;
      c.appendChild(inp);
    }
  }
}

function filterCards(filter, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.q-card').forEach(card => {
    card.style.display = (filter === 'all' || card.dataset.status === filter) ? '' : 'none';
  });
  // Скрываем заголовки тем, если все карточки темы скрыты
  document.querySelectorAll('[data-topic-header]').forEach(h => {
    const topic = h.dataset.topicHeader;
    const visible = [...document.querySelectorAll(`.q-card[data-topic="${topic}"]`)]
      .some(c => c.style.display !== 'none');
    h.style.display = visible ? '' : 'none';
  });
}

buildHiddenInputs();
</script>
</body>
</html>
