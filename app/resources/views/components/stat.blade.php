@props(['value', 'label', 'tone' => 'lagoon'])
@php $color = $tone === 'plum' ? 'text-plum-700' : ($tone === 'ink' ? 'text-ink-900' : 'text-accent-700'); @endphp
<x-card {{ $attributes }}>
    <p class="text-4xl leading-none font-extrabold tracking-tight {{ $color }} sm:text-5xl">{{ $value }}</p>
    <p class="mt-2 text-sm font-semibold text-ink-700">{{ $label }}</p>
</x-card>
