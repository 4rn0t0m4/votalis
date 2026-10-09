@props(['title'])
<div class="mx-auto max-w-md">
    <h1 class="mb-6 text-3xl font-extrabold tracking-tight">{{ $title }}</h1>
    <div class="card rise">
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="mt-5 text-sm text-ink-700">{{ $footer }}</div>
    @endisset
</div>
