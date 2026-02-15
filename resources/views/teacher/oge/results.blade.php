@extends('layouts.teacher')

@section('title', 'ОГЭ - Результаты')
@section('header', 'ОГЭ: Результаты варианта')

@section('content')
@php $taskNumbers = range(6, 19); @endphp

<div class="space-y-4">
    <div class="bg-dark-light rounded-xl border border-gray-800 p-5">
        <a href="{{ route('teacher.oge.variants', $variant->owner_teacher_id) }}" class="text-sm text-coral hover:text-coral-light">← К вариантам учителя</a>
        <h2 class="text-white text-xl font-semibold mt-2">Вариант {{ $variant->hash }}</h2>
        <p class="text-gray-400 text-sm">Владелец: {{ $variant->ownerTeacher->name ?? '—' }} · Попыток: {{ $attempts->count() }}</p>
    </div>

    <div class="bg-dark-light rounded-xl border border-gray-800 overflow-x-auto">
        <table class="min-w-[1200px] w-full text-xs">
            <thead class="bg-dark-lighter text-gray-400 uppercase tracking-wide">
                <tr>
                    <th class="text-left px-3 py-2">Ученик</th>
                    <th class="text-left px-3 py-2">Статус</th>
                    @foreach($taskNumbers as $taskNumber)
                        <th class="text-left px-3 py-2">{{ $taskNumber }}</th>
                    @endforeach
                    <th class="text-left px-3 py-2">Общее время</th>
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
                    <tr class="border-t border-gray-800 align-top">
                        <td class="px-3 py-3 text-white">
                            <div>{{ $attempt->student->name ?? '—' }}</div>
                            <div class="text-gray-500">{{ $attempt->student->email ?? '' }}</div>
                        </td>
                        <td class="px-3 py-3">
                            <span class="px-2 py-1 rounded {{ $attempt->status === 'submitted' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
                                {{ $attempt->status }}
                            </span>
                        </td>
                        @foreach($taskNumbers as $taskNumber)
                            @php
                                $answer = $answersMap->get($taskNumber);
                                $timing = $timingsMap->get($taskNumber);
                                $scoring = $scoringMap->get($taskNumber);
                            @endphp
                            <td class="px-3 py-3 text-gray-300">
                                <div class="font-medium">{{ $answer?->current_answer ?? '—' }}</div>
                                <div class="text-gray-500">{{ round(($timing?->active_ms ?? 0) / 1000) }}с</div>
                                @if(!is_null($scoring?->is_correct))
                                    <div class="{{ $scoring->is_correct ? 'text-emerald-400' : 'text-red-400' }}">
                                        {{ $scoring->is_correct ? 'верно' : 'ошибка' }}
                                    </div>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-3 py-3 text-gray-300">{{ $totalMinutes }} мин</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="20" class="px-4 py-8 text-center text-gray-500">Пока нет попыток по этому варианту.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
