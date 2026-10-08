@props(['tone' => 'neutral', 'as' => 'span'])
@php
    $classes = 'pill '.match ($tone) {
        'lagoon' => 'bg-accent-100 text-accent-800',
        'plum' => 'bg-plum-100 text-plum-700',
        'sand' => 'bg-sand-200 text-ink-700',
        'ink' => 'bg-ink-900 text-white',
        'outline' => 'border-2 border-ink-200 bg-white text-ink-700',
        default => 'bg-ink-100 text-ink-700',
    };
@endphp
<{{ $as }} {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</{{ $as }}>
