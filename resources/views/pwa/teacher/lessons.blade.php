@extends('layouts.pwa')
@section('title', 'Уроки — palomatika')

@push('styles')
  .lessons-stack { display: flex; flex-direction: column; gap: 12px; }
  .legend { display:flex; gap:8px; flex-wrap:wrap; }
  .day-head { margin: 18px 0 2px; font-size: 13px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
  .day-head.today { color: var(--accent); }
  .lesson-slot { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 12px; }
  .lesson-slot.clickable { cursor: pointer; transition: border-color .12s, transform .06s; }
  .lesson-slot.clickable:hover { border-color: var(--accent); }
  .lesson-slot.clickable:active { transform: scale(.995); }
  .lesson-slot.clickable:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
  .lesson-slot-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
  .lesson-slot-title { font-size: 16px; font-weight: 800; color: var(--text); }
  .lesson-slot-subtitle { margin-top: 4px; font-size: 12px; color: var(--muted); }
  .lesson-slot-meta { display:flex; align-items:center; gap:8px; }
  .lesson-slot-chevron { font-size: 22px; line-height: 1; color: var(--muted); }
  .lesson-slot-students { display:flex; flex-direction:column; gap:8px; margin-top:12px; }
  .lesson-slot-student {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:10px 12px; border-radius:12px; background: var(--surface2); border:1px solid var(--border);
  }
  .lesson-slot-student strong { display:block; font-size:14px; color:var(--text); }
  .lesson-slot-student span { display:block; margin-top:4px; font-size:11px; color:var(--muted); }
  .lesson-slot-actions { display:flex; gap:8px; flex-wrap:wrap; }
  .status-tag {
    display:inline-flex; align-items:center; justify-content:center;
    min-height:24px; padding:0 10px; border-radius:999px; font-size:11px; font-weight:800;
  }
  .status-tag.red { color: var(--red); background: var(--red-bg); border: 1px solid var(--red-bd); }
  .status-tag.green { color: var(--green); background: var(--green-bg); border: 1px solid var(--green-bd); }
  .status-tag.accent { color: var(--accent); background: var(--accent-bg); border: 1px solid var(--accent-bd); }
  .status-tag.yellow { color: var(--yellow); background: var(--yellow-bg); border: 1px solid var(--yellow-bd); }
@endpush

@section('body')
<div class="page" x-data="lessonsBoard()">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Уроки</div>
  </div>

  <button :disabled="creating"
          style="background: var(--accent); color: white; border: none; border-radius: 12px; padding: 14px; font-weight: 800; font-size: 14px; cursor: pointer;"
          @click="createAdhoc()">
    <span x-show="!creating">🎯 Начать новый урок</span>
    <span x-show="creating" x-cloak>создаём…</span>
  </button>

  <div class="note">
    Нажмите на урок, чтобы зайти внутрь и заранее собрать список заданий и тем. Статусы:
    <div class="legend" style="margin-top:8px;">
      <span class="status-tag red">прошёл</span>
      <span class="status-tag green">идёт</span>
      <span class="status-tag accent">будет</span>
      <span class="status-tag yellow">черновик</span>
    </div>
  </div>

  @forelse($days as $day)
    <div class="day-head {{ $day['is_today'] ? 'today' : '' }}">{{ $day['label'] }}</div>
    <div class="lessons-stack">
      @foreach($day['slots'] as $slot)
        @php
          $clickable = $slot['session_id'] || $slot['starts_at'];
          $payload = [
            'session_id'  => $slot['session_id'],
            'starts_at'   => $slot['starts_at'],
            'ends_at'     => $slot['ends_at'],
            'student_ids' => $slot['student_ids'],
          ];
        @endphp
        <section class="lesson-slot {{ $clickable ? 'clickable' : '' }}"
                 @if($clickable)
                   role="button" tabindex="0"
                   @click="openSlot(@js($payload))"
                   @keydown.enter.prevent="openSlot(@js($payload))"
                   @keydown.space.prevent="openSlot(@js($payload))"
                 @endif>
          <div class="lesson-slot-head">
            <div>
              <div class="lesson-slot-title">{{ $slot['time_start'] }}{{ $slot['time_end'] ? ' - ' . $slot['time_end'] : '' }}</div>
              <div class="lesson-slot-subtitle">{{ count($slot['students']) }} ученик{{ count($slot['students']) === 1 ? '' : (count($slot['students']) >= 2 && count($slot['students']) <= 4 ? 'а' : 'ов') }} в слоте</div>
            </div>
            <div class="lesson-slot-meta">
              @if($slot['session_status'] === 'draft')
                <span class="status-tag yellow">черновик</span>
              @elseif($slot['session_status'] === 'live')
                <span class="status-tag green">идёт</span>
              @else
                <span class="status-tag {{ $slot['status_key'] === 'current' ? 'green' : ($slot['status_key'] === 'past' ? 'red' : 'accent') }}">{{ $slot['status_label'] }}</span>
              @endif
              @if($clickable)<span class="lesson-slot-chevron" aria-hidden="true">›</span>@endif
            </div>
          </div>

          <div class="lesson-slot-students">
            @foreach($slot['students'] as $student)
              <article class="lesson-slot-student">
                <div>
                  <strong>{{ $student['student_name'] }}</strong>
                  <span>{{ $student['student_full_name'] ?: $student['evrium_name'] }}</span>
                </div>
                <div class="lesson-slot-actions">
                  @if($student['student_id'])
                    <a href="/students/{{ $student['student_id'] }}" class="btn btn-surface" @click.stop>Профиль</a>
                    <a href="/homework" class="btn btn-surface" @click.stop>Дать ДЗ</a>
                  @else
                    <span class="status-tag red">Не привязан</span>
                  @endif
                </div>
              </article>
            @endforeach
          </div>
        </section>
      @endforeach
    </div>
  @empty
    <div class="note">Расписание пусто. Как только Evrium вернёт уроки, здесь появятся слоты на ближайшие дни — со статусами, списком учеников и переходом внутрь урока.</div>
  @endforelse
</div>

<script>
  function lessonsBoard() {
    return {
      creating: false,
      opening: null,

      csrf() {
        return document.querySelector('meta[name=csrf-token]').content;
      },

      async createAdhoc() {
        if (this.creating) return;
        this.creating = true;
        try {
          const r = await fetch('/lessons', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
            credentials: 'include',
          });
          const d = await r.json();
          if (d.session) { window.location = '/lessons/' + d.session.id; }
          else { alert('Ошибка создания'); this.creating = false; }
        } catch (e) {
          alert('Ошибка сети'); this.creating = false;
        }
      },

      async openSlot(slot) {
        if (this.opening) return;
        // Уже есть черновик/идущий урок для этого времени — заходим напрямую.
        if (slot.session_id) { window.location = '/lessons/' + slot.session_id; return; }
        if (!slot.starts_at) return;

        this.opening = slot.starts_at;
        try {
          const r = await fetch('/lessons/from-slot', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
            credentials: 'include',
            body: JSON.stringify({
              starts_at: slot.starts_at,
              ends_at: slot.ends_at,
              student_ids: slot.student_ids || [],
            }),
          });
          const d = await r.json();
          if (d.session) { window.location = '/lessons/' + d.session.id; }
          else { alert(d.error || 'Не удалось открыть урок'); this.opening = null; }
        } catch (e) {
          alert('Ошибка сети'); this.opening = null;
        }
      },
    };
  }
</script>
@endsection
