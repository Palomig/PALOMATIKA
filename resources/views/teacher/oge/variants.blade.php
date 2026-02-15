@extends('layouts.teacher')

@section('title', 'ОГЭ - Варианты')
@section('header', 'ОГЭ: Варианты учителя')

@section('content')
<div class="space-y-4">
    <div class="bg-dark-light rounded-xl border border-gray-800 p-5">
        <a href="{{ route('teacher.oge.teachers') }}" class="text-sm text-coral hover:text-coral-light">← К списку учителей</a>
        <h2 class="text-white text-xl font-semibold mt-2">{{ $teacher->name }}</h2>
        <p class="text-gray-400 text-sm">Список вариантов, созданных этим учителем.</p>
    </div>

    <div class="bg-dark-light rounded-xl border border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-dark-lighter text-gray-400 uppercase text-xs tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Hash</th>
                    <th class="text-left px-4 py-3">Название</th>
                    <th class="text-left px-4 py-3">Попыток</th>
                    <th class="text-left px-4 py-3">Создан</th>
                    <th class="text-right px-4 py-3">Действие</th>
                </tr>
            </thead>
            <tbody>
                @forelse($variants as $variant)
                    <tr class="border-t border-gray-800">
                        <td class="px-4 py-3 text-emerald-400 font-mono">{{ $variant->hash }}</td>
                        <td class="px-4 py-3 text-white">{{ $variant->title ?? 'Вариант ' . $variant->hash }}</td>
                        <td class="px-4 py-3 text-gray-300">{{ $variant->attempts_count }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ optional($variant->created_at)->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('teacher.oge.results', $variant->id) }}" class="px-3 py-1.5 rounded-lg bg-coral/15 text-coral hover:bg-coral/25 transition">
                                Результаты
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">У этого учителя пока нет вариантов.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
