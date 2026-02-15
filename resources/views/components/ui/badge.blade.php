@props([
    // Badge text
    'label' => '',
    // Color: coral | green | blue | yellow | purple | gray
    'color' => 'coral',
    // Size: sm | md
    'size' => 'sm',
])

@php
    $colors = [
        'coral'  => 'bg-coral/20 text-coral',
        'green'  => 'bg-emerald-500/20 text-emerald-400',
        'blue'   => 'bg-blue-500/20 text-blue-400',
        'yellow' => 'bg-amber-500/20 text-amber-400',
        'purple' => 'bg-purple-500/20 text-purple-400',
        'gray'   => 'bg-gray-500/20 text-gray-400',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-3 py-1 text-sm',
    ];

    $colorClass = $colors[$color] ?? $colors['coral'];
    $sizeClass = $sizes[$size] ?? $sizes['sm'];
@endphp

<span {{ $attributes->merge(['class' => $colorClass . ' ' . $sizeClass . ' rounded-badge font-medium inline-flex items-center']) }}>
    {{ $label ?: $slot }}
</span>
