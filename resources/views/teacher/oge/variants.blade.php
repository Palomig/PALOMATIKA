@extends('layouts.teacher')

@section('title', 'ОГЭ - Варианты')
@section('header', 'ОГЭ: Варианты учителя')

@section('content')
<div class="space-y-5">
    {{-- Header card --}}
    <div class="relative overflow-hidden tsh-card p-5 sm:p-6">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-violet-50 to-transparent pointer-events-none"></div>
        <a href="{{ route('teacher.oge.teachers') }}"
           class="inline-flex items-center gap-1.5 text-xs font-medium transition mb-3 hover:opacity-80" style="color: var(--tsh-blue)">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            К списку учителей
        </a>
        <p class="tsh-page-kicker">Exam review</p>
        <h2 class="tsh-page-title">{{ $teacher->name }}</h2>
        <p class="tsh-page-subtitle">Список вариантов, созданных этим учителем, с быстрым переходом к результатам.</p>
    </div>

    {{-- Variants table --}}
    <div class="tsh-card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr style="color: var(--tsh-subtle); background: var(--tsh-surface-soft)">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider">Hash</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider">Название</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider">Попыток</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider">Создан</th>
                    <th class="text-right px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($variants as $variant)
                    <tr style="border-top: 1px solid var(--tsh-border-soft)"
                        onmouseover="this.style.background='var(--tsh-surface-soft)'"
                        onmouseout="this.style.background='white'">
                        <td class="px-5 py-3.5">
                            <span class="font-mono bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-md text-xs">{{ $variant->hash }}</span>
                        </td>
                        <td class="px-5 py-3.5 font-medium" style="color: var(--tsh-text)">{{ $variant->title ?? 'Вариант ' . $variant->hash }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-md tabular-nums" style="background: var(--tsh-surface-soft); color: var(--tsh-text)">
                                {{ $variant->attempts_count }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5" style="color: var(--tsh-muted)">{{ optional($variant->created_at)->format('d.m.Y H:i') }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('teacher.oge.results', $variant->id) }}"
                               class="tsh-action-primary text-xs">
                                Результаты
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm" style="color: var(--tsh-muted)">У этого учителя пока нет вариантов.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
