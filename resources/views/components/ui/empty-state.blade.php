@props([
    // Main heading
    'title' => 'Пока ничего нет',
    // Description text
    'description' => null,
    // Icon SVG path (d attribute for 24x24 viewBox)
    'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
    // Action button text (optional)
    'action' => null,
    // Action button href (optional)
    'actionHref' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 px-6 text-center']) }}>
    <div class="w-16 h-16 bg-dark rounded-2xl flex items-center justify-center mb-4 border border-gray-800">
        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/>
        </svg>
    </div>
    <h3 class="text-lg font-medium text-gray-300 mb-1">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-gray-500 max-w-sm">{{ $description }}</p>
    @endif
    @if($action && $actionHref)
        <a href="{{ $actionHref }}" class="mt-4 inline-flex items-center px-4 py-2 bg-coral text-white rounded-button text-sm font-medium hover:bg-coral-dark transition">
            {{ $action }}
        </a>
    @endif
    {{ $slot }}
</div>
