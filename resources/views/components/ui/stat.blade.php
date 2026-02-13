@props([
    // Main metric value, e.g. 5600+
    'value',
    // Label under metric
    'label',
])

<div {{ $attributes->merge(['class' => 'bg-dark-light border border-gray-800 rounded-card p-4 text-center']) }}>
    <div class="text-2xl font-bold text-coral">{{ $value }}</div>
    <div class="text-xs text-gray-400 mt-1">{{ $label }}</div>
</div>
