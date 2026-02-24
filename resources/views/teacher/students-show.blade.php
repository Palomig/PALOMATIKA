@extends('layouts.teacher')

@section('title', 'Статистика ученика')
@section('header', 'Статистика ученика')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="text-xl font-semibold text-white">{{ $student->name ?? 'Ученик' }}</div>
            <div class="text-sm text-gray-400">{{ $student->email ?? '—' }}</div>
        </div>
        <a href="{{ route('teacher.students') }}"
           class="inline-flex items-center gap-2 border border-white/[0.08] text-gray-300 px-4 py-2 rounded-xl text-sm hover:bg-white/[0.04] hover:text-white transition">
            Назад к списку
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Попытки</div>
            <div class="mt-2 text-2xl font-semibold text-coral tabular-nums">{{ $summary['attempts'] }}</div>
        </div>
        <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Точность</div>
            <div class="mt-2 text-2xl font-semibold text-white tabular-nums">
                {{ $summary['accuracy_percent'] !== null ? ($summary['accuracy_percent'] . '%') : '—' }}
            </div>
            <div class="mt-1 text-xs text-gray-500">
                {{ $summary['correct'] }}/{{ $summary['scored'] }} проверенных
            </div>
        </div>
        <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Последняя активность</div>
            <div class="mt-2 text-sm font-medium text-white">
                {{ $summary['last_activity_at'] ? $summary['last_activity_at']->diffForHumans() : 'Нет данных' }}
            </div>
            <div class="mt-1 text-xs text-gray-500">
                {{ $summary['last_activity_at'] ? $summary['last_activity_at']->format('Y-m-d H:i') : '—' }}
            </div>
        </div>
    </div>

    <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-4 sm:p-5">
        <div class="text-sm font-semibold text-white mb-4">История попыток</div>

        @forelse($attemptHistory as $attempt)
            <div class="rounded-2xl border border-white/[0.06] bg-dark/40 p-4 mb-4 last:mb-0" id="attempt-{{ $attempt['id'] }}">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-white">{{ $attempt['variant_title'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            Попытка #{{ $attempt['id'] }}
                            @if($attempt['variant_hash'] !== '')
                                • {{ $attempt['variant_hash'] }}
                            @endif
                            @if($attempt['status'] !== '')
                                • {{ $attempt['status'] }}
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $attempt['activity_at'] ? ('Активность: ' . $attempt['activity_at']->diffForHumans()) : 'Активность: —' }}
                            @if($attempt['submitted_at'])
                                • Сдано: {{ $attempt['submitted_at']->format('Y-m-d H:i') }}
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-xs">
                        <span class="px-2.5 py-1 rounded-lg bg-white/[0.04] text-gray-300">
                            Проверено: {{ $attempt['scored_count'] }}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-white/[0.04] text-gray-300">
                            Ошибок: {{ count($attempt['wrong_tasks']) }}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg {{ $attempt['accuracy_percent'] !== null && $attempt['accuracy_percent'] >= 70 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-300' }}">
                            {{ $attempt['accuracy_percent'] !== null ? ($attempt['accuracy_percent'] . '%') : '—' }}
                        </span>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Ошибки по задачам</div>

                    @if(count($attempt['wrong_tasks']) === 0)
                        <div class="text-sm text-gray-500">Ошибок нет.</div>
                    @else
                        <div class="space-y-3">
                            @foreach($attempt['wrong_tasks'] as $wrongTask)
                                <div class="rounded-xl border border-white/[0.05] bg-dark-light/60 p-3">
                                    <div class="text-sm text-white">
                                        Задание <span class="font-semibold" data-wrong-task-number>{{ $wrongTask['display_task_number'] }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-300" data-wrong-task-text>{{ $wrongTask['task_text'] }}</div>
                                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                        <div class="rounded-lg bg-red-500/5 border border-red-500/20 px-3 py-2">
                                            <div class="text-xs text-red-300 uppercase tracking-wider">Ответ ученика</div>
                                            <div class="mt-1 text-white" data-student-answer>{{ $wrongTask['student_answer'] }}</div>
                                        </div>
                                        <div class="rounded-lg bg-emerald-500/5 border border-emerald-500/20 px-3 py-2">
                                            <div class="text-xs text-emerald-300 uppercase tracking-wider">Правильный ответ</div>
                                            <div class="mt-1 text-white" data-correct-answer>{{ $wrongTask['correct_answer'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">Для этого ученика пока нет доступных попыток.</div>
        @endforelse
    </div>
</div>
@endsection
