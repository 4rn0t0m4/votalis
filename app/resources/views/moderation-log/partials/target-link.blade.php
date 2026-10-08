@php($target = $entry->target)
@if ($entry->target_type === 'user')
    <span class="text-ink-500">· titulaire non identifié</span>
@elseif ($entry->showsTarget() && $target !== null)
    @if ($target instanceof \App\Models\Proposal)
        · <a href="{{ $target->url() }}" class="link">{{ $target->title }}</a>
    @elseif ($target instanceof \App\Models\Argument && $target->proposal)
        · <a href="{{ $target->url() }}" class="link">sur « {{ $target->proposal->title }} »</a>
    @endif
@elseif (! $entry->showsTarget())
    <span class="text-ink-500">· contenu non reproduit</span>
@else
    <span class="text-ink-500">· contenu supprimé</span>
@endif
