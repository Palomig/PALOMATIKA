@extends('layouts.miniapp')
@section('title', 'Ученики — palomatika')

@push('styles')
  .search { display: flex; gap: 8px; }
  .search input { flex: 1; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px; color: var(--text); }
  .student-list { display: flex; flex-direction: column; gap: 10px; }
  .student { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 12px; }
  .student-name { font-size: 14px; font-weight: 800; color: var(--text); }
  .student-email { font-size: 11px; color: var(--muted); margin-top: 2px; }
  .alias-row { display: flex; gap: 8px; margin-top: 10px; }
  .alias-row input { flex: 1; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 10px; color: var(--text); font-size: 12px; }
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
        <div class="student-name">{{ $student->name }}</div>
        <div class="student-email">{{ $student->email }}</div>
        <div class="alias-row">
          <input type="text" x-model="aliases[{{ $student->id }}]" placeholder="Алиас для этого ученика">
          <button class="btn btn-accent" type="button" @click="saveAlias({{ $student->id }})">Сохранить</button>
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
    async saveAlias(studentId) {
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
