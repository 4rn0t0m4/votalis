@props(['title'])
<div class="mx-auto max-w-md">
    <h1 class="mb-6 text-2xl font-semibold">{{ $title }}</h1>
    <div class="rounded-lg border border-ink-200 bg-white p-6 shadow-sm">
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="mt-4 text-sm text-ink-700">{{ $footer }}</div>
    @endisset
</div>
