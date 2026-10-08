@props(['type' => 'info'])
@php
    [$classes, $icon] = match ($type) {
        'error' => ['border-red-800/30 bg-red-50 text-red-900', 'cross'],
        'success' => ['border-accent-600/40 bg-accent-100 text-accent-800', 'check'],
        'celebrate' => ['border-plum-600 bg-plum-100 text-plum-700', 'star'],
        default => ['border-ink-200 bg-ink-100 text-ink-900', 'sparkle'],
    };
@endphp
<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-2xl border-2 px-4 py-3 text-sm {$classes}"]) }}>
    <x-icon :name="$icon" class="mt-0.5 size-5" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
