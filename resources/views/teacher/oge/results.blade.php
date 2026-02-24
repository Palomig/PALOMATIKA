@extends('layouts.teacher')

@section('title', 'ОГЭ - Результаты')
@section('header', 'ОГЭ: Результаты варианта')

@section('content')
<div class="space-y-5">
    {{-- Header card --}}
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-5">
        <a href="{{ route('teacher.oge.variants', $variant->owner_teacher_id) }}"
           class="inline-flex items-center gap-1.5 text-xs font-medium text-coral hover:text-coral-light transition mb-3">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            К вариантам учителя
        </a>
        <h2 class="text-white text-lg font-semibold">Вариант {{ $variant->hash }}</h2>
        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1.5 text-sm text-gray-500">
            <span>Владелец: <span class="text-gray-300">{{ $variant->ownerTeacher->name ?? '—' }}</span></span>
            <span>Попыток: <span class="text-gray-300">{{ $attempts->count() }}</span></span>
            @if($variant->isCustomRandom())
                <span>Тип: <span class="text-sky-300">Кастомный</span></span>
            @endif
        </div>
    </div>

    {{-- Results matrix --}}
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="px-5 py-4 border-b border-white/[0.06] flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-white">Матрица результатов</h3>
                <p class="text-xs text-gray-500 mt-0.5">Строки: задания, столбцы: ученики, значения: проверка (+ / - / .)</p>
            </div>
            <div class="text-xs text-gray-500">
                <span class="text-emerald-400">+</span> верно ·
                <span class="text-red-400">-</span> неверно ·
                <span class="text-gray-400">.</span> нет проверки
            </div>
        </div>

        @if(count($resultsMatrix['students']) > 0)
            <div class="sm:hidden px-4 pb-4 space-y-3" data-mobile-results-matrix>
                <p class="text-[11px] text-gray-500">
                    Мобильный вид: карточка ученика + все задания варианта (реальные номера заданий).
                </p>
                @foreach($resultsMatrix['students'] as $studentIndex => $studentColumn)
                    @php
                        $statusValue = (string) ($studentColumn['status'] ?? '');
                        $statusLabel = $statusValue === 'submitted' ? 'Сдано' : ($statusValue !== '' ? $statusValue : '—');
                    @endphp
                    <article
                        data-mobile-student-card="{{ $studentColumn['attempt_id'] }}"
                        class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-3"
                        aria-label="Результаты ученика {{ $studentColumn['student_name'] }}"
                    >
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <a href="#attempt-{{ $studentColumn['attempt_id'] }}" class="min-w-0 flex items-center gap-2.5">
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white/[0.03] border border-white/[0.06] text-sm font-semibold text-white flex-shrink-0">
                                    {{ $studentColumn['student_short_name'] }}
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-white truncate">{{ $studentColumn['student_name'] }}</span>
                                    <span class="block text-[11px] text-gray-500">ID попытки: {{ $studentColumn['attempt_id'] }}</span>
                                </span>
                            </a>
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-medium whitespace-nowrap {{ $statusValue === 'submitted' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-white/[0.04] text-gray-400' }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-2" role="list" aria-label="Проверка по заданиям">
                            @foreach($resultsMatrix['rows'] as $row)
                                @php
                                    $cell = $row['cells'][$studentIndex] ?? ['is_correct' => null, 'mark' => '.'];
                                    $state = $cell['is_correct'];
                                    $stateLabel = $state === true ? 'верно' : ($state === false ? 'неверно' : 'нет проверки');
                                @endphp
                                <div
                                    role="listitem"
                                    data-mobile-task-chip="{{ $studentColumn['attempt_id'] }}-{{ $row['task_number'] }}"
                                    data-mobile-task-number="{{ $row['task_number'] }}"
                                    data-mobile-mark="{{ $cell['mark'] }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg border px-2 py-1.5 text-xs {{ $state === true ? 'border-emerald-500/20 bg-emerald-500/5' : ($state === false ? 'border-red-500/20 bg-red-500/5' : 'border-white/[0.06] bg-white/[0.02]') }}"
                                    aria-label="Задание {{ $row['task_number'] }}: {{ $stateLabel }}"
                                >
                                    <span class="text-gray-400 tabular-nums">№{{ $row['task_number'] }}</span>
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-md text-[11px] font-semibold tabular-nums {{ $state === true ? 'bg-emerald-500/10 text-emerald-400' : ($state === false ? 'bg-red-500/10 text-red-400' : 'bg-white/[0.03] text-gray-400') }}">
                                        {{ $cell['mark'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden sm:block px-3 pb-3 sm:px-5 sm:pb-4" data-matrix-scroll-root>
                <div data-matrix-scroll-hint class="hidden sm:block md:hidden mb-2 text-[11px] text-gray-500">
                    Свайп влево/вправо, чтобы увидеть столбцы учеников
                </div>
                <div data-matrix-scroll-container class="overflow-x-auto overflow-y-visible max-w-full touch-pan-x">
                    <table class="w-max min-w-full text-xs table-auto">
                        <thead>
                            <tr class="border-b border-white/[0.06]">
                                <th class="sticky left-0 z-20 bg-dark-light text-left px-4 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider min-w-[56px] whitespace-nowrap">
                                    №
                                </th>
                                @foreach($resultsMatrix['students'] as $studentColumn)
                                    <th class="px-3 py-3 text-center text-[11px] font-semibold text-gray-400 uppercase tracking-wider min-w-[58px] whitespace-nowrap">
                                        <a href="#attempt-{{ $studentColumn['attempt_id'] }}"
                                           class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white/[0.03] border border-white/[0.05] hover:border-coral/40 hover:text-white transition"
                                           title="{{ $studentColumn['student_name'] }}">
                                            {{ $studentColumn['student_short_name'] }}
                                        </a>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            @foreach($resultsMatrix['rows'] as $row)
                                <tr class="hover:bg-white/[0.01] transition-colors">
                                    <td class="sticky left-0 z-10 bg-dark-light px-4 py-2.5 text-gray-300 font-semibold tabular-nums min-w-[56px] whitespace-nowrap">
                                        <span data-matrix-row-task-number="{{ $row['task_number'] }}">
                                        {{ $row['task_number'] }}
                                        </span>
                                    </td>
                                    @foreach($row['cells'] as $cell)
                                        @php
                                            $state = $cell['is_correct'];
                                        @endphp
                                        <td class="px-3 py-2.5 text-center min-w-[58px] whitespace-nowrap">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md text-xs font-semibold tabular-nums {{ $state === true ? 'bg-emerald-500/10 text-emerald-400' : ($state === false ? 'bg-red-500/10 text-red-400' : 'bg-white/[0.03] text-gray-400') }}">
                                                {{ $cell['mark'] }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="px-4 py-12 text-center text-gray-600 text-sm">
                Пока нет попыток по этому варианту.
            </div>
        @endif
    </div>

    @if(count($resultsMatrix['students']) > 0)
        <script>
            (() => {
                const initMatrixScrollHints = () => {
                    document.querySelectorAll('[data-matrix-scroll-root]').forEach((root) => {
                        const container = root.querySelector('[data-matrix-scroll-container]');
                        const hint = root.querySelector('[data-matrix-scroll-hint]');

                        if (!container || !hint || root.dataset.matrixScrollBound === '1') {
                            return;
                        }

                        const updateHintVisibility = () => {
                            const hasOverflow = container.scrollWidth > container.clientWidth + 1;
                            hint.classList.toggle('hidden', !hasOverflow || container.scrollLeft > 8);
                        };

                        container.addEventListener('scroll', updateHintVisibility, { passive: true });
                        window.addEventListener('resize', updateHintVisibility, { passive: true });

                        root.dataset.matrixScrollBound = '1';
                        requestAnimationFrame(updateHintVisibility);
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initMatrixScrollHints, { once: true });
                } else {
                    initMatrixScrollHints();
                }
            })();
        </script>
    @endif

    {{-- Detailed results table --}}
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="px-5 py-4 border-b border-white/[0.06]">
            <h3 class="text-sm font-semibold text-white">Подробные ответы</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full text-xs">
                <thead>
                    <tr class="border-b border-white/[0.06]">
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider sticky left-0 bg-dark-light z-10">Ученик</th>
                        <th class="text-left px-3 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Статус</th>
                        @foreach($taskColumns as $taskColumn)
                            <th class="text-center px-2 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $taskColumn['display_task_number'] }}</th>
                        @endforeach
                        <th class="text-center px-3 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Время</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    @forelse($attempts as $attempt)
                        @php
                            $answersMap = $attempt->answers->keyBy('task_number');
                            $timingsMap = $attempt->taskTimings->keyBy('task_number');
                            $scoringMap = $attempt->scorings->keyBy('task_number');
                            $totalMs = (int) $attempt->taskTimings->sum('active_ms');
                            $totalMinutes = round($totalMs / 60000, 1);
                        @endphp
                        <tr id="attempt-{{ $attempt->id }}" class="hover:bg-white/[0.02] transition-colors align-top">
                            <td class="px-4 py-3 sticky left-0 bg-dark-light">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 bg-coral/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <span class="text-[11px] font-semibold text-coral">{{ substr($attempt->student->name ?? '?', 0, 1) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-white font-medium truncate">{{ $attempt->student->name ?? '—' }}</div>
                                        <div class="text-gray-600 truncate">{{ $attempt->student->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-md {{ $attempt->status === 'submitted' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400' }}">
                                    {{ $attempt->status === 'submitted' ? 'Сдано' : $attempt->status }}
                                </span>
                            </td>
                            @foreach($taskColumns as $taskColumn)
                                @php
                                    $attemptTaskNumber = (int) ($taskColumn['attempt_task_number'] ?? 0);
                                    $answer = $answersMap->get($attemptTaskNumber);
                                    $timing = $timingsMap->get($attemptTaskNumber);
                                    $scoring = $scoringMap->get($attemptTaskNumber);
                                @endphp
                                <td class="px-2 py-3 text-center">
                                    @if($answer?->current_answer)
                                        <div class="font-medium text-gray-200">{{ $answer->current_answer }}</div>
                                        <div class="text-gray-600 tabular-nums">{{ round(($timing?->active_ms ?? 0) / 1000) }}с</div>
                                        @if(!is_null($scoring?->is_correct))
                                            <div class="mt-0.5">
                                                @if($scoring->is_correct)
                                                    <span class="inline-block w-4 h-4 rounded-full bg-emerald-500/15 text-emerald-400 text-[10px] leading-4 text-center">+</span>
                                                @else
                                                    <span class="inline-block w-4 h-4 rounded-full bg-red-500/15 text-red-400 text-[10px] leading-4 text-center">-</span>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-700">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-3 text-center text-gray-400 tabular-nums font-medium">{{ $totalMinutes }} мин</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="px-4 py-12 text-center text-gray-600 text-sm">
                                Пока нет попыток по этому варианту.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
