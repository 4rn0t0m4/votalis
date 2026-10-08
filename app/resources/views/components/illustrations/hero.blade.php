<svg {{ $attributes->merge(['class' => 'w-full max-w-md']) }} viewBox="0 0 420 340" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <rect x="40" y="200" width="340" height="120" rx="32" class="fill-sand-100"/>
    <circle cx="130" cy="150" r="96" class="fill-accent-100"/>
    <circle cx="290" cy="130" r="80" class="fill-plum-100"/>
    <path d="M60 230 A120 120 0 0 1 300 230" fill="none" stroke-width="18" stroke-linecap="round" class="stroke-accent-600"/>
    <path d="M150 260 A90 90 0 0 1 380 260" fill="none" stroke-width="18" stroke-linecap="round" class="stroke-plum-600"/>
    <g class="fill-ink-900">
        <circle cx="90" cy="290" r="5"/><circle cx="120" cy="290" r="5"/><circle cx="150" cy="290" r="5"/><circle cx="180" cy="290" r="5"/><circle cx="210" cy="290" r="5"/><circle cx="240" cy="290" r="5"/><circle cx="270" cy="290" r="5"/><circle cx="300" cy="290" r="5"/><circle cx="330" cy="290" r="5"/>
    </g>
    <circle cx="220" cy="110" r="18" class="fill-accent-600"/>
    <circle cx="220" cy="110" r="30" fill="none" stroke-width="3" class="stroke-accent-600 opacity-40"/>
</svg>
