@extends('layouts.teacher')

@section('title', 'ОГЭ - Учителя')
@section('header', 'ОГЭ: Учителя')

@section('content')
<div class="space-y-4">
    <div class="bg-dark-light rounded-xl border border-gray-800 p-5">
        <h2 class="text-white text-xl font-semibold mb-2">Учителя</h2>
        <p class="text-gray-400 text-sm">Выбери учителя, чтобы открыть список его вариантов ОГЭ (режим просмотра).</p>
    </div>

    <div class="bg-dark-light rounded-xl border border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-dark-lighter text-gray-400 uppercase text-xs tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Учитель</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Вариантов</th>
                    <th class="text-right px-4 py-3">Действие</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $teacher)
                    <tr class="border-t border-gray-800">
                        <td class="px-4 py-3 text-white">{{ $teacher->name }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $teacher->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-300">{{ $teacher->owned_oge_variants_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('teacher.oge.variants', $teacher->id) }}" class="px-3 py-1.5 rounded-lg bg-coral/15 text-coral hover:bg-coral/25 transition">
                                Открыть
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">Учителя не найдены</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
