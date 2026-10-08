@php($target = $entry->target)
@if ($entry->showsTarget() && $target !== null)
    @if ($target instanceof \App\Models\Proposal)
        · <a href="{{ $target->url() }}" class="underline">{{ $target->title }}</a>
    @elseif ($target->proposal)
        · <a href="{{ $target->url() }}" class="underline">sur « {{ $target->proposal->title }} »</a>
    @endif
@elseif (! $entry->showsTarget())
    <span class="text-ink-500">· contenu non reproduit</span>
@else
    <span class="text-ink-500">· contenu supprimé</span>
@endif
