@props([
    // Input type: text, email, password, number, tel, url
    'type' => 'text',
    // Label text shown above input
    'label' => null,
    // Placeholder text
    'placeholder' => '',
    // Error message (shown below input)
    'error' => null,
    // Alpine.js x-model binding
    'xModel' => null,
])

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $label }}</label>
    @endif
    <input
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        @if($xModel) x-model="{{ $xModel }}" @endif
        {{ $attributes->merge(['class' => 'w-full bg-dark border border-gray-700 text-white px-4 py-3 rounded-input focus:outline-none focus:border-coral focus:ring-1 focus:ring-coral/30 transition placeholder-gray-500' . ($error ? ' border-danger' : '')]) }}
    >
    @if($error)
        <p class="mt-1 text-sm text-danger">{{ $error }}</p>
    @endif
</div>
