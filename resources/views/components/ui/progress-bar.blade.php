@props([
    // Current value (0-100 or raw)
    'value' => 0,
    // Maximum value (if value is raw, percentage = value/max*100)
    'max' => 100,
    // Color variant: coral | green | blue | orange
    'color' => 'coral',
    // Show percentage text
    'showLabel' => false,
    // Bar height: sm (1.5) | md (2) | lg (3)
    'height' => 'md',
])

@php
    $pct = $max > 0 ? min(100, round($value / $max * 100)) : 0;

    $colors = [
        'coral'  => 'bg-gradient-to-r from-coral to-coral-light',
        'green'  => 'bg-gradient-to-r from-emerald-500 to-emerald-400',
        'blue'   => 'bg-gradient-to-r from-blue-500 to-blue-400',
        'orange' => 'bg-gradient-to-r from-orange-500 to-amber-400',
    ];

    $heights = [
        'sm' => 'h-1.5',
        'md' => 'h-2',
        'lg' => 'h-3',
    ];

    $barColor = $colors[$color] ?? $colors['coral'];
    $barHeight = $heights[$height] ?? $heights['md'];
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    @if($showLabel)
        <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>{{ $pct }}%</span>
        </div>
    @endif
    <div class="bg-dark rounded-full {{ $barHeight }}">
        <div class="{{ $barColor }} rounded-full {{ $barHeight }} transition-all duration-300"
             style="width: {{ $pct }}%"></div>
    </div>
</div>
