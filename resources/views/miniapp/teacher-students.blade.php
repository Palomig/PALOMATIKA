@extends('layouts.miniapp')
@section('title', 'Ученики — palomatika')

@push('styles')
  .search { display: flex; gap: 8px; }
  .search input { flex: 1; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px; color: var(--text); }
  .student-list { display: flex; flex-direction: column; gap: 10px; }
  .student { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 12px; }
  .student-head { display:flex; align-items:center; gap:10px; }
  .avatar { width:32px; height:32px; border-radius:999px; object-fit:cover; background:#1f2937; flex-shrink:0; border:1px solid var(--border); }
  .avatar-fallback { width:32px; height:32px; border-radius:999px; display:flex; align-items:center; justify-content:center; background:#1f2937; color:#cbd5e1; font-size:11px; font-weight:800; flex-shrink:0; border:1px solid var(--border); text-transform:uppercase; }
  .student-name { font-size: 14px; font-weight: 800; color: var(--text); }
  .student-email { font-size: 11px; color: var(--muted); margin-top: 2px; }
  .alias-row { display: flex; gap: 8px; margin-top: 10px; }
  .alias-row input { flex: 1; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 10px; color: var(--text); font-size: 12px; }
  .ownership { margin-top: 8px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
  .ownership-badge { font-size: 11px; color: var(--muted); }
  .pager { display: flex; justify-content: center; }
@endpush

@section('body')
<div class="page" x-data="studentsPage()">
  <div class="topbar">
    <a href="/tg/teacher/dashboard" class="back-btn">‹</a>
    <div class="topbar-title">Ученики</div>
  </div>

  <form method="GET" action="/tg/teacher/students" class="search">
    <input type="text" name="search" value="{{ $search }}" placeholder="Имя, email или алиас">
    <button type="submit" class="btn btn-surface">Поиск</button>
  </form>

  <div class="student-list">
    @forelse($students as $student)
      <div class="student" data-student-id="{{ $student->id }}">
        <div class="student-head">
          @if(!empty($student->avatar))
            <img class="avatar" src="{{ $student->avatar }}" alt="avatar">
          @else
            <div class="avatar-fallback">{{ mb_substr($student->name, 0, 1) }}</div>
          @endif
          <div>
            <div class="student-name">{{ $student->name }}</div>
            <div class="student-email">{{ $student->email }}</div>
          </div>
        </div>
        <div class="ownership">
          <div class="ownership-badge" x-text="isMine[{{ $student->id }}] ? 'Помечен как мой ученик' : 'Не помечен как мой' "></div>
          <div style="display:flex; gap:8px;">
            <a class="btn btn-surface" href="/tg/teacher/students/{{ $student->id }}">Профиль</a>
            <button class="btn btn-surface" type="button" @click="toggleOwnership({{ $student->id }})" x-text="isMine[{{ $student->id }}] ? 'Не мой' : 'Мой'"></button>
          </div>
        </div>
        <div class="alias-row">
          <input type="text" x-model="aliases[{{ $student->id }}]" :disabled="!isMine[{{ $student->id }}]" placeholder="Алиас для этого ученика">
          <button class="btn btn-accent" type="button" :disabled="!isMine[{{ $student->id }}]" @click="saveAlias({{ $student->id }})">Сохранить</button>
        </div>
      </div>
    @empty
      <div class="note">Список пуст.</div>
    @endforelse
  </div>

  @if($students->hasPages())
    <div class="pager">{{ $students->links() }}</div>
  @endif
</div>
@endsection

@push('scripts')
<script>
function studentsPage() {
  return {
    aliases: @json($students->getCollection()->mapWithKeys(fn($s) => [$s->id => $s->student_alias])->all()),
    isMine: @json($students->getCollection()->mapWithKeys(fn($s) => [$s->id => (bool)($s->is_mine ?? false)])->all()),

    async toggleOwnership(studentId) {
      const res = await fetch(`/tg/teacher/students/${studentId}/ownership`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': window._csrf,
        },
      });

      if (!res.ok) {
        const tg = window.Telegram?.WebApp;
        if (tg?.showAlert) tg.showAlert('Не удалось изменить статус ученика');
        return;
      }

      const data = await res.json();
      this.isMine[studentId] = !!data.is_mine;
      if (!this.isMine[studentId]) {
        this.aliases[studentId] = '';
      }
    },

    async saveAlias(studentId) {
      if (!this.isMine[studentId]) return;
      const alias = (this.aliases[studentId] || '').trim();
      const res = await fetch(`/tg/teacher/students/${studentId}/alias`, {
        method: 'PATCH',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window._csrf,
        },
        body: JSON.stringify({ alias }),
      });

      if (!res.ok) {
        const tg = window.Telegram?.WebApp;
        if (tg?.showAlert) tg.showAlert('Не удалось сохранить алиас');
        return;
      }

      const data = await res.json();
      this.aliases[studentId] = data.alias || '';
    }
  };
}
</script>
@endpush
