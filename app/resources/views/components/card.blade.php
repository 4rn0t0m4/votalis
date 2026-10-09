@props(['tone' => 'white', 'as' => 'div', 'lift' => false])
@php
    $classes = match ($tone) {
        'flat' => 'card-flat',
        'lagoon' => 'card-lagoon',
        'plum' => 'card-plum',
        'sand' => 'card-sand',
        'ink' => 'card-ink',
        default => 'card',
    }.($lift ? ' card-lift' : '');
@endphp
<{{ $as }} {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</{{ $as }}>
