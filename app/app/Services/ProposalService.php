<?php

namespace App\Services;

use App\Enums\ProposalOrigin;
use App\Enums\RevisionKind;
use App\Jobs\ComputeProposalEmbedding;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Création et modification des fiches de proposition : validation du format imposé,
 * thème ouvert, plafonds, sources, révisions, verrou du fond après le premier vote.
 */
class ProposalService
{
    public function __construct(private readonly ContributionCaps $caps, private readonly ModerationService $moderation) {}

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input, ?User $author, ProposalOrigin $origin = ProposalOrigin::Citizen, ?string $seedSource = null): Proposal
    {
        $data = $this->validate($input);

        /** @var Theme $theme */
        $theme = Theme::query()->findOrFail($data['theme_id']);

        if ($origin === ProposalOrigin::Citizen && ! $theme->acceptsProposals()) {
            throw ValidationException::withMessages(['theme_id' => [__('Ce thème n’accepte plus de nouvelles propositions.')]]);
        }

        if ($theme->isArchived()) {
            throw ValidationException::withMessages(['theme_id' => [__('Ce thème est archivé.')]]);
        }

        if ($author !== null) {
            $this->caps->assertCanCreateProposal($author, $theme);
        }

        return DB::transaction(function () use ($data, $author, $origin, $seedSource): Proposal {
            $proposal = Proposal::create([
                'theme_id' => $data['theme_id'],
                'author_id' => $author?->id,
                'title' => $data['title'],
                'problem' => $data['problem'],
                'measure' => $data['measure'],
                'cost_estimate' => $data['cost_estimate'],
                'cost_unknown' => $data['cost_unknown'],
                'origin' => $origin,
                'seed_source' => $seedSource,
            ]);

            $this->syncSources($proposal, $data);
            $this->record($proposal, $author, RevisionKind::Content);

            ComputeProposalEmbedding::dispatch($proposal->id)->afterCommit();

            return $proposal;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(Proposal $proposal, array $input, User $author): Proposal
    {
        $data = $this->validate($input);
        $kind = RevisionKind::Content;
        // Reformulation demandée par la modération : une modification de fond malgré le verrou.
        $rewrite = $proposal->awaitsRewrite() && $proposal->author_id === $author->id;

        if ($proposal->isLocked() && ! $rewrite) {
            $this->assertTypoOnly($proposal, $data);
            $kind = RevisionKind::Typo;
        } elseif ($data['theme_id'] !== $proposal->theme_id) {
            /** @var Theme $theme */
            $theme = Theme::query()->findOrFail($data['theme_id']);

            if (! $theme->acceptsProposals()) {
                throw ValidationException::withMessages(['theme_id' => [__('Ce thème n’accepte plus de nouvelles propositions.')]]);
            }
        }

        return DB::transaction(function () use ($proposal, $data, $author, $kind, $rewrite): Proposal {
            $proposal->fill([
                'theme_id' => $data['theme_id'],
                'title' => $data['title'],
                'problem' => $data['problem'],
                'measure' => $data['measure'],
                'cost_estimate' => $data['cost_estimate'],
                'cost_unknown' => $data['cost_unknown'],
            ])->save();

            $this->syncSources($proposal, $data);
            $this->record($proposal, $author, $kind);

            if ($rewrite) {
                $this->moderation->rewriteReceived($proposal);
            }

            if ($kind === RevisionKind::Content) {
                ComputeProposalEmbedding::dispatch($proposal->id)->afterCommit();
            }

            return $proposal->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $input): array
    {
        $data = ProposalRules::normalize($input);

        Validator::make($data, ProposalRules::rules(), ProposalRules::messages())
            ->after(ProposalRules::afterSources(...))
            ->validate();

        return $data;
    }

    /**
     * Fiche verrouillée : seules les corrections de forme sur le titre, le problème et la
     * mesure sont admises ; thème, coût et sources ne changent plus.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function assertTypoOnly(Proposal $proposal, array $data): void
    {
        $message = __('Cette proposition a déjà reçu des votes : seules les corrections de forme sont possibles. Un changement de fond passe par une variante.');

        $currentSources = $proposal->sources->map(fn ($s) => $s->is_personal ? '__personal__' : (string) $s->url)->all();
        /** @var list<string> $newSources */
        $newSources = $data['sources'];

        if ($data['personal_source']) {
            $newSources[] = '__personal__';
        }

        sort($currentSources);
        sort($newSources);

        if ($data['theme_id'] !== $proposal->theme_id
            || $data['cost_unknown'] !== $proposal->cost_unknown
            || $data['cost_estimate'] !== $proposal->cost_estimate
            || $currentSources !== $newSources) {
            throw ValidationException::withMessages(['locked' => [$message]]);
        }

        $ratio = (float) config('votalis.typo_ratio', 0.10);

        foreach (['title', 'problem', 'measure'] as $field) {
            if (TextSimilarity::changeRatio((string) $proposal->{$field}, (string) $data[$field]) > $ratio) {
                throw ValidationException::withMessages([$field => [$message]]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncSources(Proposal $proposal, array $data): void
    {
        $proposal->sources()->delete();

        foreach ($data['sources'] as $url) {
            $proposal->sources()->create(['url' => $url, 'is_personal' => false]);
        }

        if ($data['personal_source']) {
            $proposal->sources()->create(['url' => null, 'is_personal' => true]);
        }

        $proposal->unsetRelation('sources');
    }

    private function record(Proposal $proposal, ?User $author, RevisionKind $kind): void
    {
        $proposal->load('sources');
        $proposal->revisions()->create([
            'author_id' => $author?->id,
            'kind' => $kind,
            'snapshot' => $proposal->snapshot(),
        ]);
    }
}
