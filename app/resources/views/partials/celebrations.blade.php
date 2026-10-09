{{-- Célébration discrète des jalons nouvellement atteints : `$milestones` (list<Milestone>), affichée une seule fois. --}}
@foreach ($milestones as $milestone)
    <x-alert type="celebrate" class="pop mt-4">
        <span class="eyebrow block text-plum-700">Jalon atteint, visible par vous seul·e</span>
        <strong class="text-base">{{ $milestone->label() }}</strong> · {{ $milestone->rule() }}
        <a href="{{ route('account.journey') }}" class="link ml-1">Mon parcours</a>
    </x-alert>
@endforeach
