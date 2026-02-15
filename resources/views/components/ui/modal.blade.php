@props([
    // Alpine.js x-show variable name
    'show' => 'showModal',
    // Modal title
    'title' => null,
    // Max width: sm | md | lg | xl
    'maxWidth' => 'md',
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
    ];
    $widthClass = $widths[$maxWidth] ?? $widths['md'];
@endphp

<div x-show="{{ $show }}" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     role="dialog" aria-modal="true" @if($title) aria-label="{{ $title }}" @endif>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="{{ $show }} = false"></div>

    {{-- Panel --}}
    <div class="relative w-full {{ $widthClass }} bg-dark-light rounded-card border border-gray-800 shadow-2xl"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @keydown.escape.window="{{ $show }} = false">
        @if($title)
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
                <h3 class="text-lg font-semibold text-white">{{ $title }}</h3>
                <button @click="{{ $show }} = false" class="text-gray-400 hover:text-white transition" aria-label="Закрыть">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif
        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</div>
