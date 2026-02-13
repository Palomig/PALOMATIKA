@props([
    // Optional heading text rendered at the top of card
    'title' => null,
])

<article {{ $attributes->merge(['class' => 'rounded-card border border-gray-800 bg-dark-light p-6 transition-card']) }}>
    @if ($title)
        <h3 class="text-xl font-semibold text-white mb-2">{{ $title }}</h3>
    @endif

    {{ $slot }}
</article>
