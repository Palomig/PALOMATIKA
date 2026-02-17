@extends('layouts.teacher')

@section('title', 'ОГЭ - Учителя')
@section('header', 'ОГЭ: Учителя')

@section('content')
<div class="space-y-5">
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-5">
        <h2 class="text-white text-lg font-semibold mb-1">Учителя</h2>
        <p class="text-sm text-gray-500">Выбери учителя, чтобы открыть список его вариантов ОГЭ (режим просмотра).</p>
    </div>

    <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
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
