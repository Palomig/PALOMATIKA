@extends('layouts.teacher')

@section('title', 'ОГЭ - Учителя')
@section('header', 'ОГЭ: Учителя')

@section('content')
<div class="space-y-5">
    <div class="relative overflow-hidden tsh-card p-5 sm:p-6">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-violet-50 to-transparent pointer-events-none"></div>
        <div class="relative">
            <p class="tsh-page-kicker">Exam review</p>
            <h2 class="tsh-page-title">Учителя и варианты ОГЭ</h2>
            <p class="tsh-page-subtitle">Выберите преподавателя, чтобы перейти к его вариантам и детальным результатам учеников.</p>
        </div>
    </div>

    <div class="tsh-card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr style="color: var(--tsh-subtle); background: var(--tsh-surface-soft)">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider">Учитель</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider">Email</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider">Вариантов</th>
                    <th class="text-right px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $teacher)
                    <tr style="border-top: 1px solid var(--tsh-border-soft)"
                        onmouseover="this.style.background='var(--tsh-surface-soft)'"
                        onmouseout="this.style.background='white'">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-semibold">{{ substr($teacher->name, 0, 1) }}</span>
                                </div>
                                <span class="font-medium" style="color: var(--tsh-text)">{{ $teacher->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5" style="color: var(--tsh-muted)">{{ $teacher->email ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-md tabular-nums" style="background: var(--tsh-surface-soft); color: var(--tsh-text)">
                                {{ $teacher->owned_oge_variants_count }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('teacher.oge.variants', $teacher->id) }}"
                               class="tsh-action-primary text-xs">
                                Открыть
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-sm" style="color: var(--tsh-muted)">Учителя не найдены</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
