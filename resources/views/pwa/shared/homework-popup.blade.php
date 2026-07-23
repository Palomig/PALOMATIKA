@if(!empty($homeworkPopup))
<div class="hwpop-overlay" x-data="{ open: true }" x-show="open" x-cloak
     @keydown.escape.window="open = false">
  <div class="hwpop-card" @click.outside="open = false">
    <div class="hwpop-emoji">📚</div>
    <div class="hwpop-title">У тебя есть невыполненная домашка</div>
    <div class="hwpop-name">{{ $homeworkPopup['title'] }}</div>
    <div class="hwpop-meta">
      Сделано {{ $homeworkPopup['done'] }} из {{ $homeworkPopup['total'] }}
      @if(!empty($homeworkPopup['deadline'])) · срок до {{ $homeworkPopup['deadline'] }} @endif
    </div>
    <a href="{{ $homeworkPopup['url'] }}" class="hwpop-btn hwpop-btn-go">Перейти к ДЗ</a>
    <button type="button" class="hwpop-btn hwpop-btn-close" @click="open = false">Закрыть</button>
  </div>
</div>
<style>
  .hwpop-overlay { position: fixed; inset: 0; z-index: 1200; background: rgba(0,0,0,.55);
    display: flex; align-items: center; justify-content: center; padding: 24px; }
  .hwpop-card { background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb);
    border-radius: 18px; width: 100%; max-width: 360px; padding: 24px 20px; text-align: center;
    display: flex; flex-direction: column; gap: 10px; box-shadow: 0 12px 40px rgba(0,0,0,.35); }
  .hwpop-emoji { font-size: 40px; line-height: 1; }
  .hwpop-title { font-size: 18px; font-weight: 800; color: var(--text, #111); }
  .hwpop-name { font-size: 15px; font-weight: 700; color: var(--accent, #6d28d9); }
  .hwpop-meta { font-size: 13px; color: var(--muted, #6b7280); }
  .hwpop-btn { display: block; width: 100%; padding: 13px; border-radius: 12px; font-size: 15px;
    font-weight: 800; cursor: pointer; text-align: center; border: none; margin-top: 2px; }
  .hwpop-btn-go { background: var(--accent, #6d28d9); color: #fff; text-decoration: none; }
  .hwpop-btn-close { background: none; color: var(--muted, #6b7280); font-weight: 700; }
</style>
@endif
