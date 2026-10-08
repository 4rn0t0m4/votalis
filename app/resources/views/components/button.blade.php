@props(['variant' => 'primary', 'type' => 'submit'])
@php
    $classes = match ($variant) {
        'secondary' => 'border border-ink-300 bg-white text-ink-900 hover:bg-ink-100',
        'danger' => 'border border-red-800/40 bg-white text-red-900 hover:bg-red-50',
        default => 'bg-accent-600 text-white hover:bg-accent-700',
    };
@endphp
<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex min-h-11 items-center justify-center rounded px-4 py-2 text-sm font-medium disabled:opacity-50 {$classes}"]) }}>
    {{ $slot }}
</button>
