@extends('layouts.teacher')

@section('title', 'Материалы JARVIS')
@section('header', 'Материалы JARVIS')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl bg-dark-light border border-dark-lighter p-5">
        <h2 class="text-white text-xl font-semibold">Управление материалами</h2>
        <p class="text-gray-300 mt-2 text-sm">Создавайте и публикуйте материалы через API <code class="text-coral">/api/materials</code>.</p>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-3">
        @forelse($materials as $material)
            <div class="rounded-2xl bg-dark-light border border-dark-lighter p-4">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="text-sm font-medium text-white truncate">{{ $material->title }}</div>
                    <span class="text-xs px-2 py-0.5 rounded-md flex-shrink-0 {{ $material->status === 'published' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-white/[0.04] text-gray-400' }}">{{ $material->status }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span class="font-mono truncate">{{ $material->slug }}</span>
                    <span>{{ optional($material->updated_at)->format('d.m.Y') }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-dark-light border border-dark-lighter p-8 text-center text-sm text-gray-400">
                No materials yet.
            </div>
        @endforelse
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block rounded-2xl bg-dark-light border border-dark-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-dark-lighter">
                        <th class="px-5 py-3">Title</th>
                        <th class="px-5 py-3">Slug</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Updated</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($materials as $material)
                    <tr class="border-b border-dark-lighter/60 text-sm">
                        <td class="px-5 py-3 text-white">{{ $material->title }}</td>
                        <td class="px-5 py-3 text-gray-400">{{ $material->slug }}</td>
                        <td class="px-5 py-3 text-gray-300">{{ $material->status }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ optional($material->updated_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-5 py-6 text-gray-400" colspan="4">No materials yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
