<svg {{ $attributes->merge(['class' => 'w-40']) }} viewBox="0 0 160 120" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <rect x="20" y="30" width="120" height="70" rx="20" class="fill-sand-100"/>
    <circle cx="60" cy="60" r="26" class="fill-accent-100"/>
    <circle cx="105" cy="55" r="18" class="fill-plum-100"/>
    <path d="M40 92 A40 40 0 0 1 120 92" fill="none" stroke-width="8" stroke-linecap="round" class="stroke-accent-600"/>
    <circle cx="80" cy="42" r="7" class="fill-ink-900"/>
</svg>
