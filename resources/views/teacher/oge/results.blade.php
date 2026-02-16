@extends('layouts.teacher')

@section('title', 'ОГЭ - Результаты')
@section('header', 'ОГЭ: Результаты варианта')

@section('content')
@php $taskNumbers = range(6, 19); @endphp

<div class="space-y-5">
    {{-- Header card --}}
    <div class="relative overflow-hidden tsh-card p-5 sm:p-6">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-violet-50 to-transparent pointer-events-none"></div>
        <a href="{{ route('teacher.oge.variants', $variant->owner_teacher_id) }}"
           class="inline-flex items-center gap-1.5 text-xs font-medium transition mb-3 hover:opacity-80" style="color: var(--tsh-blue)">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            К вариантам учителя
        </a>
        <p class="tsh-page-kicker">Exam review</p>
        <h2 class="tsh-page-title">Вариант {{ $variant->hash }}</h2>
        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1.5 text-sm" style="color: var(--tsh-muted)">
            <span>Владелец: <span style="color: var(--tsh-text)">{{ $variant->ownerTeacher->name ?? '—' }}</span></span>
            <span>Попыток: <span style="color: var(--tsh-text)">{{ $attempts->count() }}</span></span>
        </div>
    </div>

    {{-- Results table --}}
    <div class="tsh-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full text-xs">
                <thead>
                    <tr style="color: var(--tsh-subtle); background: var(--tsh-surface-soft)">
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider sticky left-0 z-10" style="background: var(--tsh-surface-soft)">Ученик</th>
                        <th class="text-left px-3 py-3 text-[11px] font-semibold uppercase tracking-wider">Статус</th>
                        @foreach($taskNumbers as $taskNumber)
                            <th class="text-center px-2 py-3 text-[11px] font-semibold uppercase tracking-wider">{{ $taskNumber }}</th>
                        @endforeach
                        <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider">Время</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attempts as $attempt)
                        @php
                            $answersMap = $attempt->answers->keyBy('task_number');
                            $timingsMap = $attempt->taskTimings->keyBy('task_number');
                            $scoringMap = $attempt->scorings->keyBy('task_number');
                            $totalMs = (int) $attempt->taskTimings->sum('active_ms');
                            $totalMinutes = round($totalMs / 60000, 1);
                        @endphp
                        <tr class="align-top hover:bg-[#f9fafb]" style="border-top: 1px solid var(--tsh-border-soft)">
                            <td class="px-4 py-3 sticky left-0" style="background: var(--tsh-surface)">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-[11px] font-semibold">{{ substr($attempt->student->name ?? '?', 0, 1) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium truncate" style="color: var(--tsh-text)">{{ $attempt->student->name ?? '—' }}</div>
                                        <div class="truncate" style="color: var(--tsh-muted)">{{ $attempt->student->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-md {{ $attempt->status === 'submitted' ? 'bg-emerald-50 text-emerald-500' : 'bg-amber-50 text-amber-500' }}">
                                    {{ $attempt->status === 'submitted' ? 'Сдано' : $attempt->status }}
                                </span>
                            </td>
                            @foreach($taskNumbers as $taskNumber)
                                @php
                                    $answer = $answersMap->get($taskNumber);
                                    $timing = $timingsMap->get($taskNumber);
                                    $scoring = $scoringMap->get($taskNumber);
                                @endphp
                                <td class="px-2 py-3 text-center">
                                    @if($answer?->current_answer)
                                        <div class="font-medium" style="color: var(--tsh-text)">{{ $answer->current_answer }}</div>
                                        <div class="tabular-nums" style="color: var(--tsh-muted)">{{ round(($timing?->active_ms ?? 0) / 1000) }}с</div>
                                        @if(!is_null($scoring?->is_correct))
                                            <div class="mt-0.5">
                                                @if($scoring->is_correct)
                                                    <span class="inline-block w-4 h-4 rounded-full bg-emerald-100 text-emerald-500 text-[10px] leading-4 text-center">+</span>
                                                @else
                                                    <span class="inline-block w-4 h-4 rounded-full bg-red-100 text-red-500 text-[10px] leading-4 text-center">-</span>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <span style="color: var(--tsh-muted)">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-3 text-center tabular-nums font-medium" style="color: var(--tsh-muted)">{{ $totalMinutes }} мин</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="px-4 py-12 text-center text-sm" style="color: var(--tsh-muted)">
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
