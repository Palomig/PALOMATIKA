@extends('layouts.teacher')

@section('title', 'ОГЭ - Варианты')
@section('header', 'ОГЭ: Варианты учителя')

@section('content')
<div class="space-y-5">
    {{-- Header card --}}
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-5">
        <a href="{{ route('teacher.oge.teachers') }}"
           class="inline-flex items-center gap-1.5 text-xs font-medium text-coral hover:text-coral-light transition mb-3">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            К списку учителей
        </a>
        <h2 class="text-white text-lg font-semibold">{{ $teacher->name }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">Список вариантов, созданных этим учителем.</p>
    </div>

    {{-- Variants table --}}
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/[0.06]">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Hash</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Название</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Попыток</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Создан</th>
                    <th class="text-right px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($variants as $variant)
                    <tr class="hover:bg-white/[0.02] transition-colors">
                        <td class="px-5 py-3.5">
                            <span class="font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-md text-xs">{{ $variant->hash }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-white font-medium">
                            <div class="flex items-center gap-2">
                                <span>{{ $variant->title ?? 'Вариант ' . $variant->hash }}</span>
                                @if($variant->isCustomRandom())
                                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-md bg-sky-500/15 text-sky-300">Кастомный</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-lg bg-white/[0.04] text-gray-300 tabular-nums">
                                {{ $variant->attempts_count }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500">{{ optional($variant->created_at)->format('d.m.Y H:i') }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('teacher.oge.results', $variant->id) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-coral/10 text-coral text-xs font-medium hover:bg-coral/20 transition">
                                Результаты
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-gray-600 text-sm">У этого учителя пока нет вариантов.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
