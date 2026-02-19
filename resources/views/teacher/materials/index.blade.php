@extends('layouts.teacher')

@section('title', 'Материалы JARVIS')
@section('header', 'Материалы JARVIS')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl bg-dark-light border border-dark-lighter p-5">
        <h2 class="text-white text-xl font-semibold">Управление материалами</h2>
        <p class="text-gray-300 mt-2">Создавайте и публикуйте материалы через API `/api/materials`.</p>
    </div>

    <div class="rounded-2xl bg-dark-light border border-dark-lighter overflow-hidden">
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
