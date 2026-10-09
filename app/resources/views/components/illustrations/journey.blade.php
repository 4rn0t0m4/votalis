<svg {{ $attributes->merge(['class' => 'w-full max-w-xs']) }} viewBox="0 0 320 160" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <path d="M20 120 C80 20, 140 200, 200 80 S280 40, 300 60" fill="none" stroke-width="10" stroke-linecap="round" class="stroke-plum-200"/>
    <path d="M20 120 C80 20, 140 200, 200 80" fill="none" stroke-width="10" stroke-linecap="round" class="stroke-plum-600"/>
    <circle cx="20" cy="120" r="12" class="fill-plum-600"/>
    <circle cx="110" cy="92" r="12" class="fill-plum-600"/>
    <circle cx="200" cy="80" r="14" class="fill-white stroke-plum-600" stroke-width="6"/>
    <circle cx="300" cy="60" r="12" class="fill-plum-200"/>
    <rect x="236" y="100" width="64" height="40" rx="14" class="fill-sand-100"/>
</svg>
