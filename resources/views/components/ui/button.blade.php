@props([
    // Render as link when href is set, otherwise as button
    'href' => null,
    // primary | ghost | outline
    'variant' => 'primary',
    // sm | md | lg
    'size' => 'md',
    // Button type when href is not set
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center rounded-button font-semibold transition focus-ring';

    $variants = [
        'primary' => 'bg-coral text-white hover:bg-coral-dark shadow-glow-coral',
        'ghost' => 'text-gray-300 hover:text-white hover:bg-dark-lighter',
        'outline' => 'border border-gray-600 text-white hover:bg-dark-lighter',
    ];

    $sizes = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-5 py-2.5 text-base',
        'lg' => 'px-7 py-4 text-lg',
    ];

    $className = trim($base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $className]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $className]) }}>
        {{ $slot }}
    </button>
@endif
