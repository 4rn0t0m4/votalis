@props(['type' => 'info'])
@php
    $classes = match ($type) {
        'error' => 'border-red-800/30 bg-red-50 text-red-900',
        'success' => 'border-accent-700/30 bg-accent-100 text-accent-700',
        default => 'border-ink-300 bg-ink-100 text-ink-900',
    };
@endphp
<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->merge(['class' => "rounded border px-4 py-3 text-sm {$classes}"]) }}>
    {{ $slot }}
</div>
