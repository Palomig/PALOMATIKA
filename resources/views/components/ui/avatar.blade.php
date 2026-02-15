@props([
    // User name (first letter used as fallback)
    'name' => '?',
    // Image URL (optional)
    'src' => null,
    // sm (8) | md (10) | lg (12) | xl (16)
    'size' => 'md',
    // Background color class
    'bg' => 'bg-coral/20',
    // Text color class
    'color' => 'text-coral',
])

@php
    $sizes = [
        'sm' => 'w-8 h-8 text-sm',
        'md' => 'w-10 h-10 text-base',
        'lg' => 'w-12 h-12 text-lg',
        'xl' => 'w-16 h-16 text-xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if($src)
    <img src="{{ $src }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => $sizeClass . ' rounded-full object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => $sizeClass . ' ' . $bg . ' rounded-full flex items-center justify-center']) }}>
        <span class="font-medium {{ $color }}">{{ mb_substr($name, 0, 1) }}</span>
    </div>
@endif
