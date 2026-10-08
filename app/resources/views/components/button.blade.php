@props(['variant' => 'primary', 'type' => 'submit', 'size' => 'md', 'href' => null])
@php
    $classes = 'btn '.match ($variant) {
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        'plum' => 'btn-plum',
        'danger' => 'btn-danger',
        default => 'btn-primary',
    }.($size === 'lg' ? ' btn-lg' : '');
@endphp
@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
