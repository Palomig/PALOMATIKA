@extends('layouts.pwa')
@section('title', 'Уроки сегодня')

@push('styles')
  .lessons-stack { display: flex; flex-direction: column; gap: 16px; }
  .slot-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
  .slot-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid var(--border); }
  .slot-time { font-family: var(--display); font-size: 18px; color: var(--text); }
  .slot-meta { font-size: 11px; color: var(--muted); margin-top: 2px; }
  .slot-status { padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; letter-spacing: 0.06em; flex-shrink: 0; }
  .slot-status.past    { color: var(--muted); background: var(--surface2); border: 1px solid var(--border); }
  .slot-status.current { color: var(--green); background: var(--green-bg); border: 1px solid var(--green-bd); }
  .slot-status.upcoming{ color: var(--accent); background: var(--accent-bg); border: 1px solid var(--accent-bd); }

  .student-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; padding: 14px 16px; }
  .student-tile {
    background: var(--surface2); border: 2px solid var(--border); border-radius: 14px;
    padding: 12px 10px; text-align: center; cursor: pointer;
    transition: border-color 0.15s, background 0.15s, transform 0.1s;
    user-select: none; -webkit-user-select: none;
  }
  .student-tile:active { transform: scale(0.95); }
  .student-tile.present { border-color: var(--green-bd); background: var(--green-bg); }
  .student-tile.absent  { border-color: var(--red-bd);   background: var(--red-bg); opacity: 0.7; }
  .student-tile.saving  { opacity: 0.5; pointer-events: none; }
  .student-tile .tile-name { font-size: 13px; font-weight: 800; color: var(--text); line-height: 1.3; }
  .student-tile .tile-badge {
    display: inline-block; margin-top: 6px; padding: 2px 8px; border-radius: 999px;
    font-size: 10px; font-weight: 700;
  }
  .student-tile.present .tile-badge { color: var(--green); background: rgba(52,208,126,0.15); }
  .student-tile.absent  .tile-badge { color: var(--red);   background: rgba(240,96,96,0.15); }
  .student-tile.unlinked { cursor: default; opacity: 0.5; }
  .student-tile .tile-unlinked { font-size: 10px; color: var(--muted); margin-top: 4px; }

  .slot-actions { display: flex; gap: 8px; padding: 0 16px 14px; }

  .report-section { display: flex; flex-direction: column; gap: 10px; }
  .report-result { padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 700; }
  .report-result.ok  { color: var(--green); background: var(--green-bg); border: 1px solid var(--green-bd); }
  .report-result.err { color: var(--red);   background: var(--red-bg);   border: 1px solid var(--red-bd); }

  .push-banner { background: var(--surface2); border: 1px solid var(--accent-bd); border-radius: 14px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
  .push-banner-text { flex: 1; font-size: 12px; color: var(--muted); line-height: 1.5; }
  .push-banner-text strong { display: block; font-size: 13px; color: var(--text); margin-bottom: 2px; }
  .push-ok { color: var(--green); font-size: 13px; font-weight: 700; }
@endpush

@section('body')
<div class="page" x-data="lessonPage()" x-init="init()">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.dashboard') }}" class="back-btn">‹</a>
    <div class="topbar-title">Уроки — {{ $todayLabel }}</div>
  </div>

  {{-- Push notification banner --}}
  <div class="push-banner" x-show="pushState === 'prompt'" x-cloak>
    <div class="push-banner-text">
      <strong>Уведомления об уроках</strong>
      Получайте напоминания за 10 минут до начала урока
    </div>
    <button class="btn btn-accent" style="padding:10px 16px;font-size:13px;" @click="enablePush()">Включить</button>
  </div>
  <div class="push-banner" x-show="pushState === 'subscribed'" x-cloak>
    <div class="push-banner-text">
      <strong>Уведомления включены</strong>
      Вы получите уведомление за 10 мин до урока
    </div>
    <span class="push-ok">✓</span>
  </div>

  @if($todayLessons)
  <div class="lessons-stack">
    @foreach($todayLessons as $lesson)
    @php
      $slotKey = $lesson['time_start'];
      $safeKey = preg_replace('/[^a-z0-9]/', '', strtolower($slotKey));
      $linkedStudents = array_filter($lesson['students'], fn($s) => $s['student_id'] !== null);
      $studentCount = count($lesson['students']);
    @endphp
    <section class="slot-card" id="slot-{{ $safeKey }}">
      <div class="slot-head">
        <div>
          <div class="slot-time">
            {{ $lesson['time_start'] }}{{ $lesson['time_end'] ? ' – ' . $lesson['time_end'] : '' }}
          </div>
          <div class="slot-meta">{{ $studentCount }} {{ trans_choice('ученик|ученика|учеников', $studentCount) }}</div>
        </div>
        <span class="slot-status {{ $lesson['status_key'] === 'current' ? 'current' : ($lesson['status_key'] === 'past' ? 'past' : 'upcoming') }}">
          {{ $lesson['status_key'] === 'current' ? 'Идёт' : ($lesson['status_key'] === 'past' ? 'Прошёл' : 'Будет') }}
        </span>
      </div>

      <div class="student-tiles">
        @foreach($lesson['students'] as $student)
        @if($student['student_id'])
          <div
            class="student-tile"
            :class="getTileClass({{ $student['student_id'] }}, '{{ $slotKey }}')"
            @click="toggleStudent({{ $student['student_id'] }}, '{{ $slotKey }}')"
          >
            <div class="tile-name">{{ $student['student_name'] }}</div>
            <div class="tile-badge" x-text="getTileBadge({{ $student['student_id'] }}, '{{ $slotKey }}')"></div>
          </div>
        @else
          <div class="student-tile unlinked">
            <div class="tile-name">{{ $student['evrium_name'] }}</div>
            <div class="tile-unlinked">Не привязан</div>
          </div>
        @endif
        @endforeach
      </div>

      @if(count(array_filter($lesson['students'], fn($s) => $s['student_id'])) > 0)
      <div class="slot-actions">
        <button class="btn btn-surface" style="flex:1;padding:10px;" @click="markAll('{{ $slotKey }}', 'present')">
          Все пришли
        </button>
        <button class="btn btn-surface" style="flex:1;padding:10px;" @click="markAll('{{ $slotKey }}', 'absent')">
          Все отсутствуют
        </button>
      </div>
      @endif
    </section>
    @endforeach
  </div>

  {{-- Daily report --}}
  <div class="report-section">
    <div class="sec-label">Отчёт за сегодня</div>
    <button
      class="btn btn-accent"
      @click="sendReport()"
      :disabled="reportSending"
      x-text="reportSending ? 'Отправляю...' : 'Отправить отчёт в Telegram'"
    ></button>
    <div class="report-result ok" x-show="reportMsg && reportOk" x-text="reportMsg" x-cloak></div>
    <div class="report-result err" x-show="reportMsg && !reportOk" x-text="reportMsg" x-cloak></div>
  </div>

  @else
  <div class="note">На сегодня уроков нет. Как только расписание Evrium вернёт слоты, здесь появятся карточки.</div>
  @endif
</div>

<script>
const TODAY = '{{ now()->toDateString() }}';
const CSRF  = document.querySelector('meta[name="csrf-token"]').content;

// Student IDs per slot (for JS) - built from Blade
const SLOT_STUDENTS = {
  @foreach($todayLessons as $lesson)
  @php $linkedIds = array_values(array_filter(array_map(fn($s) => $s['student_id'], $lesson['students']), fn($id) => $id !== null)) @endphp
  '{{ $lesson['time_start'] }}': [{{ implode(',', $linkedIds) }}],
  @endforeach
};

function lessonPage() {
  return {
    // attendance[timeStart][studentId] = 'present'|'absent'
    attendance: {},
    reportSending: false,
    reportMsg: '',
    reportOk: false,
    pushState: 'loading', // loading | prompt | subscribed | denied | unsupported

    async init() {
      // Load attendance for all slots
      const times = Object.keys(SLOT_STUDENTS);
      await Promise.all(times.map(t => this.loadSlotAttendance(t)));

      // Initialize unset students to 'present'
      for (const [time, ids] of Object.entries(SLOT_STUDENTS)) {
        if (!this.attendance[time]) this.attendance[time] = {};
        for (const id of ids) {
          if (this.attendance[time][id] === undefined) {
            this.attendance[time][id] = 'present';
          }
        }
      }

      this.initPushState();
    },

    async loadSlotAttendance(timeStart) {
      try {
        const res = await fetch(`/lessons/attendance?date=${TODAY}&time_start=${encodeURIComponent(timeStart)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (!this.attendance[timeStart]) this.attendance[timeStart] = {};
        for (const [sid, status] of Object.entries(json.attendance || {})) {
          this.attendance[timeStart][parseInt(sid)] = status;
        }
      } catch(e) {}
    },

    getTileClass(studentId, timeStart) {
      const status = this.attendance[timeStart]?.[studentId];
      return {
        'present': status === 'present' || status === undefined,
        'absent': status === 'absent',
      };
    },

    getTileBadge(studentId, timeStart) {
      const status = this.attendance[timeStart]?.[studentId];
      return (status === 'absent') ? 'Не пришёл' : 'Пришёл';
    },

    async toggleStudent(studentId, timeStart) {
      if (!this.attendance[timeStart]) this.attendance[timeStart] = {};
      const current = this.attendance[timeStart][studentId] ?? 'present';
      const next    = current === 'present' ? 'absent' : 'present';
      this.attendance[timeStart][studentId] = next;
      await this.saveAttendance(studentId, timeStart, next);
    },

    async markAll(timeStart, status) {
      const ids = SLOT_STUDENTS[timeStart] || [];
      if (!this.attendance[timeStart]) this.attendance[timeStart] = {};
      for (const id of ids) {
        this.attendance[timeStart][id] = status;
      }
      await Promise.all(ids.map(id => this.saveAttendance(id, timeStart, status)));
    },

    async saveAttendance(studentId, timeStart, status) {
      try {
        await fetch('/lessons/attendance', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            date: TODAY, time_start: timeStart,
            student_id: studentId, status,
          }),
        });
      } catch(e) {}
    },

    async sendReport() {
      this.reportSending = true;
      this.reportMsg = '';
      try {
        const res  = await fetch('/lessons/report', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
          body: JSON.stringify({ date: TODAY }),
        });
        const json = await res.json();
        this.reportOk  = json.ok;
        this.reportMsg = json.message || (json.ok ? 'Отчёт отправлен!' : 'Ошибка отправки.');
      } catch(e) {
        this.reportOk  = false;
        this.reportMsg = 'Ошибка подключения.';
      } finally {
        this.reportSending = false;
      }
    },

    async initPushState() {
      if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        this.pushState = 'unsupported';
        return;
      }
      const perm = Notification.permission;
      if (perm === 'denied') { this.pushState = 'denied'; return; }

      const reg = await navigator.serviceWorker.ready;
      const sub = await reg.pushManager.getSubscription();
      this.pushState = sub ? 'subscribed' : 'prompt';
    },

    async enablePush() {
      const perm = await Notification.requestPermission();
      if (perm !== 'granted') { this.pushState = 'denied'; return; }

      const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
      if (!vapidKey) return;

      const reg = await navigator.serviceWorker.ready;
      const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey),
      });

      await fetch('/push/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ subscription: sub.toJSON() }),
      });

      this.pushState = 'subscribed';
    },
  };
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}
</script>
@endsection
