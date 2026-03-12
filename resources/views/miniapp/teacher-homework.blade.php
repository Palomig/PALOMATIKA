@extends('layouts.miniapp')
@section('title', 'Домашка — palomatika')

@push('styles')
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
  }
  .back { color: var(--text); text-decoration: none; font-size: 18px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 10px; }
  .topbar-title { font-family: var(--display); font-size: 18px; color: var(--text); }

  .student-row {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 8px;
    opacity: 0; animation: fadeUp 0.3s ease calc(var(--i, 0) * 0.04s) forwards;
  }
  .student-name { font-size: 14px; font-weight: 700; color: var(--text); }
  .student-time { font-size: 11px; color: var(--muted); font-weight: 600; }
  .assign-btn {
    font-size: 11px; font-weight: 800; color: var(--purple);
    background: var(--purple-bg); border: 1px solid var(--purple-bd);
    border-radius: 8px; padding: 6px 12px; cursor: pointer;
    white-space: nowrap;
  }
  .assign-btn:active { opacity: 0.7; }

  .hw-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px; margin-bottom: 8px;
  }
  .hw-title { font-size: 13px; font-weight: 700; color: var(--text); }
  .hw-meta { font-size: 11px; color: var(--muted); font-weight: 600; margin-top: 2px; }
  .hw-status-badge {
    display: inline-block; font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.06em;
    padding: 2px 7px; border-radius: 5px; margin-top: 4px;
  }
  .badge-assigned { background: rgba(59,130,246,.2); color: #93bbfd; }
  .badge-started { background: rgba(234,179,8,.2); color: #fcd34d; }
  .badge-completed { background: rgba(34,197,94,.2); color: #86efac; }

  .toast {
    position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
    background: var(--green); color: #000; font-weight: 700; font-size: 13px;
    padding: 10px 20px; border-radius: 10px; z-index: 200;
    animation: fadeUp 0.3s ease forwards;
  }
  .toast-error { background: var(--red); color: #fff; }

  .empty-note { text-align: center; padding: 24px; color: var(--muted); font-size: 13px; font-weight: 600; }
@endpush

@section('body')
<div class="page" x-data="teacherHw()">
  <div class="topbar">
    <a href="/tg/teacher/dashboard" class="back">←</a>
    <div class="topbar-title">Домашка</div>
    <div style="width:34px;"></div>
  </div>

  @if(session('success'))
    <div class="toast" x-init="setTimeout(() => $el.remove(), 3000)">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="toast toast-error" x-init="setTimeout(() => $el.remove(), 3000)">{{ session('error') }}</div>
  @endif

  {{-- TODAY'S STUDENTS --}}
  @if($todaySlots->count() > 0)
  <div class="sec-label">Сегодня на уроке</div>
  @foreach($todaySlots as $i => $slot)
    <div class="student-row" style="--i:{{ $i }}">
      <div>
        <div class="student-name">{{ $slot->student?->name ?? 'Ученик #' . $slot->student_id }}</div>
        <div class="student-time">{{ substr($slot->start_time, 0, 5) }}{{ $slot->end_time ? ' – ' . substr($slot->end_time, 0, 5) : '' }}</div>
      </div>
      <button class="assign-btn" @click="openAssign({{ $slot->student_id }}, '{{ e($slot->student?->name ?? '') }}')">Дать ДЗ</button>
    </div>
  @endforeach
  @endif

  {{-- ALL STUDENTS --}}
  <div class="sec-label">Все ученики</div>
  @forelse($allStudents as $i => $s)
    <div class="student-row" style="--i:{{ $i }}">
      <div class="student-name">{{ $s->name }}</div>
      <button class="assign-btn" @click="openAssign({{ $s->id }}, '{{ e($s->name) }}')">Дать ДЗ</button>
    </div>
  @empty
    <div class="empty-note">Нет учеников.</div>
  @endforelse

  {{-- RECENT HOMEWORK --}}
  @if($recentHomework->count() > 0)
  <div class="sec-label" style="margin-top: 16px;">Выданные ДЗ</div>
  @foreach($recentHomework as $hw)
    <div class="hw-card">
      <div class="hw-title">{{ $hw->title }}</div>
      <div class="hw-meta">{{ $hw->assigned_at?->format('d.m.Y H:i') }}</div>
      @foreach($hw->assignments as $a)
        <div style="margin-top: 4px; font-size: 12px; color: var(--text);">
          {{ $a->student?->name ?? '?' }}
          <span class="hw-status-badge {{ 'badge-' . $a->status }}">{{ $a->status }}</span>
        </div>
      @endforeach
    </div>
  @endforeach
  @endif

  {{-- ASSIGN MODAL --}}
  <template x-if="showAssign">
    <div class="fv-overlay" @click.self="showAssign = false">
      <div class="fv-sheet">
        <div class="fv-handle"></div>
        <div class="fv-title">ДЗ для <span x-text="assignName"></span></div>

        <form method="POST" action="/tg/teacher/homework/assign">
          @csrf
          <input type="hidden" name="student_id" :value="assignStudentId">

          <div class="fv-option" style="cursor:pointer" @click="$refs.typeFullVariant.checked = true; selectedType = 'full_variant'">
            <div class="fv-opt-icon">📝</div>
            <div>
              <div class="fv-opt-name">Полный вариант</div>
              <div class="fv-opt-desc">Автоматически сгенерированный вариант ОГЭ</div>
            </div>
            <input type="radio" name="type" value="full_variant" x-ref="typeFullVariant" :checked="selectedType === 'full_variant'" style="margin-left:auto;">
          </div>

          <div class="fv-option" style="cursor:pointer" @click="$refs.typeTopicPractice.checked = true; selectedType = 'topic_practice'">
            <div class="fv-opt-icon">🎯</div>
            <div>
              <div class="fv-opt-name">Конкретная тема</div>
              <div class="fv-opt-desc">Прорешать все задания одной темы</div>
            </div>
            <input type="radio" name="type" value="topic_practice" x-ref="typeTopicPractice" :checked="selectedType === 'topic_practice'" style="margin-left:auto;">
          </div>

          <template x-if="selectedType === 'topic_practice'">
            <div style="margin: 12px 0;">
              <label style="font-size:12px;font-weight:700;color:var(--muted);display:block;margin-bottom:6px;">Номер задания</label>
              <select name="topic_number" style="width:100%;padding:10px;border-radius:10px;border:1px solid var(--border);background:var(--surface);color:var(--text);font-size:14px;">
                @foreach([6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,23] as $tn)
                  <option value="{{ $tn }}">Задание {{ $tn }}</option>
                @endforeach
              </select>
            </div>
          </template>

          <button type="submit" class="btn btn-accent" style="width:100%;margin-top:12px;padding:14px;font-size:14px;font-weight:800;">Назначить ДЗ</button>
        </form>

        <button class="fv-cancel" @click="showAssign = false">Отмена</button>
      </div>
    </div>
  </template>
</div>
@endsection

@push('scripts')
<script>
function teacherHw() {
  return {
    showAssign: false,
    assignStudentId: null,
    assignName: '',
    selectedType: 'full_variant',

    openAssign(id, name) {
      this.assignStudentId = id;
      this.assignName = name;
      this.selectedType = 'full_variant';
      this.showAssign = true;
    },
  };
}
</script>
@endpush
