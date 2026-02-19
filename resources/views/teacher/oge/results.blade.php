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

    {{-- Results table --}}
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full text-xs">
                <thead>
                    <tr class="border-b border-white/[0.06]">
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider sticky left-0 bg-dark-light z-10">Ученик</th>
                        <th class="text-left px-3 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Статус</th>
                        @foreach($taskNumbers as $taskNumber)
                            <th class="text-center px-2 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $taskNumber }}</th>
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
                        <tr class="hover:bg-white/[0.02] transition-colors align-top">
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
                            @foreach($taskNumbers as $taskNumber)
                                @php
                                    $answer = $answersMap->get($taskNumber);
                                    $timing = $timingsMap->get($taskNumber);
                                    $scoring = $scoringMap->get($taskNumber);
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
