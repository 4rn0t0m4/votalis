<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Enums\VoteValue;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Double vote (CDC 4.3) : un vote par compte et par proposition, révisable ; le vote initial
 * est conservé ; le premier vote verrouille le fond de la fiche ; plafond quotidien côté serveur.
 */
class VoteService
{
    public const CONDITION_MAX = 200;

    public function __construct(private readonly ContributionCaps $caps) {}

    /**
     * @param  bool  $afterArguments  Le vote est posé ou révisé depuis une vue où les arguments sont visibles.
     *
     * @throws ValidationException
     */
    public function cast(User $user, Proposal $proposal, VoteValue $desirable, VoteValue $necessary, ?string $condition, bool $afterArguments): Vote
    {
        if ($proposal->status !== ProposalStatus::Published) {
            throw ValidationException::withMessages(['proposal' => [__('Cette proposition n’est pas ouverte au vote.')]]);
        }

        if ($proposal->author_id !== null && $proposal->author_id === $user->id) {
            throw ValidationException::withMessages(['proposal' => [__('Vous ne pouvez pas voter pour votre propre proposition.')]]);
        }

        if (! $user->can('participate') || ! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['proposal' => [__('Seuls les participants vérifiés peuvent voter.')]]);
        }

        $condition = $this->normalizeCondition($condition, $desirable, $necessary);

        return DB::transaction(function () use ($user, $proposal, $desirable, $necessary, $condition, $afterArguments): Vote {
            /** @var Vote|null $vote */
            $vote = Vote::query()->where('participant_id', $user->id)->where('proposal_id', $proposal->id)->lockForUpdate()->first();

            if ($vote === null) {
                $this->caps->assertCanVote($user);

                $vote = Vote::create([
                    'participant_id' => $user->id,
                    'proposal_id' => $proposal->id,
                    'desirable' => $desirable,
                    'necessary' => $necessary,
                    'condition' => $condition,
                    'desirable_initial' => $desirable,
                    'necessary_initial' => $necessary,
                ]);

                Proposal::query()->whereKey($proposal->id)->increment('votes_count');

                if (! $proposal->isLocked()) {
                    Proposal::query()->whereKey($proposal->id)->whereNull('content_locked_at')->update(['content_locked_at' => now()]);
                }

                return $vote;
            }

            $changed = $vote->desirable !== $desirable || $vote->necessary !== $necessary;

            $vote->desirable = $desirable;
            $vote->necessary = $necessary;
            $vote->condition = $condition;

            if ($changed && $afterArguments) {
                $vote->revised_after_arguments = true;
            }

            $vote->save();

            return $vote;
        });
    }

    public function voteOf(?User $user, Proposal $proposal): ?Vote
    {
        if ($user === null) {
            return null;
        }

        return Vote::query()->where('participant_id', $user->id)->where('proposal_id', $proposal->id)->first();
    }

    /**
     * Résultats détaillés : effectifs et parts pour chaque question, oui conditionnels, conditions.
     *
     * @return array{total: int, desirable: array<string, int>, necessary: array<string, int>, conditional: int, conditions: list<string>}
     */
    public function results(Proposal $proposal): array
    {
        $rows = DB::table('votes')->where('proposal_id', $proposal->id)
            ->selectRaw('desirable, necessary, count(*) as n, sum(case when condition is not null then 1 else 0 end) as conditional')
            ->groupBy('desirable', 'necessary')
            ->get();

        $result = [
            'total' => 0,
            'desirable' => ['yes' => 0, 'no' => 0, 'unsure' => 0],
            'necessary' => ['yes' => 0, 'no' => 0, 'unsure' => 0],
            'conditional' => 0,
            'conditions' => [],
        ];

        foreach ($rows as $row) {
            $n = (int) $row->n;
            $result['total'] += $n;
            $result['conditional'] += (int) $row->conditional;
            $result['desirable'][self::key((int) $row->desirable)] += $n;
            $result['necessary'][self::key((int) $row->necessary)] += $n;
        }

        /** @var list<string> $conditions */
        $conditions = Vote::query()->where('proposal_id', $proposal->id)->whereNotNull('condition')
            ->orderByDesc('updated_at')->limit(50)->pluck('condition')->all();
        $result['conditions'] = $conditions;

        return $result;
    }

    public static function percent(int $part, int $total): int
    {
        return $total === 0 ? 0 : (int) round(100 * $part / $total);
    }

    private static function key(int $value): string
    {
        return match ($value) {
            1 => 'yes',
            -1 => 'no',
            default => 'unsure',
        };
    }

    /**
     * @throws ValidationException
     */
    private function normalizeCondition(?string $condition, VoteValue $desirable, VoteValue $necessary): ?string
    {
        $condition = trim((string) $condition);

        if ($condition === '') {
            return null;
        }

        if (mb_strlen($condition) > self::CONDITION_MAX) {
            throw ValidationException::withMessages(['condition' => [__('La condition ne doit pas dépasser :max caractères.', ['max' => self::CONDITION_MAX])]]);
        }

        if ($desirable !== VoteValue::Yes && $necessary !== VoteValue::Yes) {
            throw ValidationException::withMessages(['condition' => [__('Une condition accompagne un « oui » : répondez oui à l’une des deux questions ou retirez la condition.')]]);
        }

        return $condition;
    }
}
