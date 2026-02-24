@extends('layouts.teacher')

@section('title', 'ОГЭ - Учителя')
@section('header', 'ОГЭ: Учителя')

@section('content')
<div class="space-y-5">
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-5">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h2 class="text-white text-lg font-semibold mb-1">Учителя</h2>
                <p class="text-sm text-gray-500">Выбери учителя, чтобы открыть список его вариантов ОГЭ (режим просмотра).</p>
            </div>
            <a href="{{ route('oge.generator') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-coral text-white text-sm font-medium hover:bg-coral-dark transition whitespace-nowrap">
                Открыть генератор
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7 7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-3">
        @forelse($teachers as $teacher)
            <a href="{{ route('teacher.oge.variants', $teacher->id) }}"
               class="block bg-dark-light rounded-2xl border border-white/[0.06] p-4 active:bg-white/[0.02] transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-coral/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-semibold text-coral">{{ substr($teacher->name, 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white truncate">{{ $teacher->name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $teacher->email ?? '—' }}</div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-lg bg-white/[0.04] text-gray-300 tabular-nums">
                            {{ $teacher->owned_oge_variants_count }} вар.
                        </span>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
        @empty
            <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-8 text-center text-sm text-gray-600">
                Учителя не найдены
            </div>
        @endforelse
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/[0.06]">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Учитель</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Вариантов</th>
                    <th class="text-right px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($teachers as $teacher)
                    <tr class="hover:bg-white/[0.02] transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-coral/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-semibold text-coral">{{ substr($teacher->name, 0, 1) }}</span>
                                </div>
                                <span class="text-white font-medium">{{ $teacher->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500">{{ $teacher->email ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-lg bg-white/[0.04] text-gray-300 tabular-nums">
                                {{ $teacher->owned_oge_variants_count }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('teacher.oge.variants', $teacher->id) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-coral/10 text-coral text-xs font-medium hover:bg-coral/20 transition">
                                Открыть
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-gray-600 text-sm">Учителя не найдены</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
