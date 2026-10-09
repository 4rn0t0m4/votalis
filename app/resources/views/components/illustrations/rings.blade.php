<svg {{ $attributes->merge(['class' => 'size-28']) }} viewBox="0 0 120 120" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" class="stroke-current opacity-25"/>
    <circle cx="60" cy="60" r="30" fill="none" stroke-width="10" class="stroke-current opacity-50"/>
    <circle cx="60" cy="60" r="10" class="fill-current"/>
</svg>
