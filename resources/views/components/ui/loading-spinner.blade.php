@props([
    // sm | md | lg
    'size' => 'md',
    // Text to show below spinner
    'text' => null,
])

@php
    $sizes = [
        'sm' => 'w-5 h-5',
        'md' => 'w-8 h-8',
        'lg' => 'w-12 h-12',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center']) }}>
    <svg class="{{ $sizeClass }} animate-spin text-coral" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    @if($text)
        <p class="mt-2 text-sm text-gray-400">{{ $text }}</p>
    @endif
</div>
