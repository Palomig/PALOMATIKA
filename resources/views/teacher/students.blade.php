@extends('layouts.teacher')

@section('title', 'Ученики')
@section('header', 'Ученики')

@section('content')
<div>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
        <form method="GET" action="{{ route('teacher.students') }}" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto flex-1">
            <input type="hidden" name="scope" value="{{ $scope ?? 'all' }}">
            <div class="relative w-full sm:w-80">
                <svg class="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Поиск учеников..."
                       class="w-full pl-10 pr-4 py-2.5 bg-dark-light border border-white/[0.06] rounded-xl text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-coral/40 focus:border-coral/30 transition">
            </div>
            <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border border-white/[0.08] text-gray-300 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-white/[0.04] hover:text-white transition">
                Найти
            </button>
            @if($search !== '')
                <a href="{{ route('teacher.students') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border border-white/[0.08] text-gray-400 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-white/[0.04] hover:text-white transition">
                    Сбросить
                </a>
            @endif
        </form>

        <div class="w-full sm:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <div class="inline-flex p-1 rounded-xl bg-dark-light border border-white/[0.06]">
                <a href="{{ route('teacher.students', array_filter(['search' => $search !== '' ? $search : null, 'scope' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ ($scope ?? 'all') === 'all' ? 'bg-coral text-white' : 'text-gray-400 hover:text-white hover:bg-white/[0.04]' }}">
                    Все
                </a>
                <a href="{{ route('teacher.students', array_filter(['search' => $search !== '' ? $search : null, 'scope' => 'linked'])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ ($scope ?? 'all') === 'linked' ? 'bg-coral text-white' : 'text-gray-400 hover:text-white hover:bg-white/[0.04]' }}">
                    Привязанные
                </a>
            </div>

            <div class="inline-flex items-center justify-center gap-2 bg-coral/10 text-coral px-4 py-2.5 rounded-xl text-sm font-medium border border-coral/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>{{ $students->total() }} в списке</span>
            </div>
        </div>
    </div>

    <div class="sm:hidden space-y-3">
        @forelse($students as $student)
            <a href="{{ route('teacher.students.show', ['id' => $student->id]) }}"
               class="block bg-dark-light rounded-2xl border border-white/[0.06] p-4 hover:border-coral/30 transition"
                 data-roster-student-id="{{ $student->id }}"
                 data-linked-state="{{ ($student->is_linked ?? false) ? 'linked' : 'unlinked' }}">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-coral/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-semibold text-coral">{{ mb_substr($student->name ?? '?', 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white truncate">{{ $student->name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $student->email }}</div>
                        @if(!empty($student->student_alias))
                            <div class="text-xs text-coral/90 truncate mt-0.5">Алиас: {{ $student->student_alias }}</div>
                        @endif
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-lg {{ ($student->oge_accuracy_percent ?? null) !== null ? 'bg-emerald-500/10 text-emerald-400' : 'bg-white/[0.04] text-gray-500' }}">
                        {{ ($student->oge_accuracy_percent ?? null) !== null ? ($student->oge_accuracy_percent . '%') : 'Нет оценок' }}
                    </span>
                </div>

                <div class="mb-3">
                    <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-lg {{ ($student->is_linked ?? false) ? 'bg-emerald-500/10 text-emerald-400' : 'bg-white/[0.04] text-gray-400' }}">
                        {{ ($student->is_linked ?? false) ? 'привязан' : 'не привязан' }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div class="bg-dark/50 rounded-lg p-2 text-center">
                        <div class="text-sm font-semibold text-coral tabular-nums">{{ $student->oge_attempt_count }}</div>
                        <div class="text-[10px] text-gray-500">ОГЭ попытки</div>
                    </div>
                    <div class="bg-dark/50 rounded-lg p-2 text-center">
                        <div class="text-sm font-semibold tabular-nums {{ ($student->oge_accuracy_percent ?? 0) >= 70 ? 'text-emerald-400' : (($student->oge_accuracy_percent ?? 0) >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                            {{ ($student->oge_accuracy_percent ?? null) !== null ? ($student->oge_accuracy_percent . '%') : '—' }}
                        </div>
                        <div class="text-[10px] text-gray-500">Точность</div>
                    </div>
                    <div class="bg-dark/50 rounded-lg p-2 text-center">
                        <div class="text-xs font-medium text-gray-300 leading-tight">
                            {{ $student->roster_last_activity_at ? $student->roster_last_activity_at->diffForHumans() : 'Нет' }}
                        </div>
                        <div class="text-[10px] text-gray-500">Активность</div>
                    </div>
                </div>
            </a>
        @empty
            <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-8 text-center text-sm text-gray-500">
                Ученики не найдены
            </div>
        @endforelse
    </div>

    <div class="hidden sm:block bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/[0.06]">
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Ученик</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">ОГЭ попытки</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Точность</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Последняя активность</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider hidden xl:table-cell">Подписка</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    @forelse($students as $student)
                        <tr class="hover:bg-white/[0.02] transition-colors"
                            data-roster-student-id="{{ $student->id }}"
                            data-linked-state="{{ ($student->is_linked ?? false) ? 'linked' : 'unlinked' }}">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('teacher.students.show', ['id' => $student->id]) }}"
                                   class="flex items-center gap-3 rounded-xl -m-1 p-1 hover:bg-white/[0.02] transition">
                                    <div class="w-9 h-9 bg-coral/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-semibold text-coral">{{ mb_substr($student->name ?? '?', 0, 1) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-white truncate">{{ $student->name }}</div>
                                        <div class="text-xs text-gray-500 truncate">{{ $student->email }}</div>
                                        @if(!empty($student->student_alias))
                                            <div class="mt-1 text-xs text-coral/90 truncate">Алиас: {{ $student->student_alias }}</div>
                                        @endif
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-lg {{ ($student->is_linked ?? false) ? 'bg-emerald-500/10 text-emerald-400' : 'bg-white/[0.04] text-gray-400' }}">
                                                {{ ($student->is_linked ?? false) ? 'привязан' : 'не привязан' }}
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-sm font-medium text-coral tabular-nums">{{ $student->oge_attempt_count }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                @if(($student->oge_accuracy_percent ?? null) !== null)
                                    <span class="text-sm font-medium tabular-nums {{ $student->oge_accuracy_percent >= 70 ? 'text-emerald-400' : ($student->oge_accuracy_percent >= 50 ? 'text-amber-400' : 'text-red-400') }}">{{ $student->oge_accuracy_percent }}%</span>
                                @else
                                    <span class="text-sm text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-500 hidden lg:table-cell">
                                {{ $student->roster_last_activity_at ? $student->roster_last_activity_at->diffForHumans() : 'Нет активности' }}
                            </td>
                            <td class="px-5 py-3.5 hidden xl:table-cell">
                                @php($hasSubscription = method_exists($student, 'hasActiveSubscription') ? $student->hasActiveSubscription() : false)
                                <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-lg {{ $hasSubscription ? 'bg-emerald-500/10 text-emerald-400' : 'bg-white/[0.04] text-gray-500' }}">
                                    {{ $hasSubscription ? 'Активна' : 'Нет' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">Ученики не найдены</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($students->hasPages())
        <div class="mt-4 bg-dark-light rounded-2xl border border-white/[0.06] px-4 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="text-xs text-gray-500">
                Показаны {{ $students->firstItem() }}-{{ $students->lastItem() }} из {{ $students->total() }}
            </div>
            <div class="text-sm [&_nav>div:first-child]:hidden [&_svg]:w-4 [&_svg]:h-4 [&_span]:rounded-lg [&_a]:rounded-lg [&_a]:bg-dark [&_a]:border-white/10 [&_a]:text-gray-300 [&_span[aria-current='page']]:bg-coral [&_span[aria-current='page']]:text-white">
                {{ $students->onEachSide(1)->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
