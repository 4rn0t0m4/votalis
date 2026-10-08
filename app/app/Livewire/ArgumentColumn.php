<?php

namespace App\Livewire;

use App\Enums\ArgumentSide;
use App\Enums\ArgumentStatus;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ContributionCaps;
use App\Services\ReadOnlyMode;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Une colonne d'arguments (« pour » ou « contre ») : liste, dépôt, marque « utile ». */
class ArgumentColumn extends Component
{
    #[Locked]
    public int $proposalId;

    #[Locked]
    public ArgumentSide $side;

    public string $body = '';

    public string $source_url = '';

    public function mount(Proposal $proposal, ArgumentSide $side): void
    {
        $this->proposalId = $proposal->id;
        $this->side = $side;
    }

    public function submit(ContributionCaps $caps): void
    {
        app(ReadOnlyMode::class)->assertWritable();
        Gate::authorize('create', Argument::class);

        /** @var User $user */
        $user = auth()->user();

        $this->validate([
            'body' => ['required', 'string', 'min:10', 'max:600'],
            'source_url' => ['nullable', 'string', 'url:http,https', 'max:500'],
        ], [
            'body.required' => 'Écrivez votre argument.',
            'body.min' => 'Un argument fait au moins :min caractères.',
            'body.max' => 'Un argument ne dépasse pas :max caractères.',
            'source_url.url' => 'La source doit être une adresse web complète (https://…).',
        ]);

        $caps->assertCanCreateArgument($user);

        Argument::create([
            'proposal_id' => $this->proposalId,
            'author_id' => $user->id,
            'side' => $this->side,
            'body' => trim($this->body),
            'source_url' => trim($this->source_url) ?: null,
        ]);

        $this->reset('body', 'source_url');
        $this->dispatch('argument-added');
    }

    public function toggleMark(int $argumentId): void
    {
        /** @var Argument $argument */
        $argument = Argument::query()->published()->where('proposal_id', $this->proposalId)->findOrFail($argumentId);

        Gate::authorize('mark', $argument);

        /** @var User $user */
        $user = auth()->user();

        $user->markedArguments()->toggle([$argument->id]);
    }

    public function render(): View
    {
        $user = auth()->user();

        // Les arguments masqués restent listés sous forme de bandeau (CDC section 6), jamais leur texte.
        $arguments = Argument::query()
            ->whereIn('status', [ArgumentStatus::Published, ArgumentStatus::Hidden])
            ->where('proposal_id', $this->proposalId)
            ->where('side', $this->side->value)
            ->with('author')
            ->withCount('markedBy')
            ->orderByDesc('created_at')
            ->get();

        $marked = $user instanceof User
            ? $user->markedArguments()->whereIn('arguments.id', $arguments->pluck('id'))->pluck('arguments.id')->all()
            : [];

        return view('livewire.argument-column', [
            'arguments' => $arguments,
            'publishedCount' => $arguments->filter(fn (Argument $a) => $a->isPublished())->count(),
            'marked' => $marked,
            'canReport' => $user instanceof User && $user->can('participate'),
            'sideEnum' => $this->side,
            'sideKey' => $this->side->value,
            'canContribute' => $user instanceof User && Gate::forUser($user)->allows('create', Argument::class),
        ]);
    }
}
