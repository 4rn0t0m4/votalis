<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\VoteConditionGroup;
use Illuminate\Support\Facades\DB;

/**
 * Regroupe les conditions « oui, à condition que… » d'une proposition par similarité
 * sémantique (CDC 4.3). Regroupement glouton : chaque condition rejoint le premier groupe
 * dont le représentant lui est assez proche, sinon ouvre un groupe. Le libellé du groupe est
 * la condition la plus centrale.
 */
class ConditionGrouper
{
    public function __construct(private readonly EmbeddingClient $client) {}

    /**
     * @param  list<string>  $conditions
     * @return list<array{label: string, count: int, conditions: list<string>}>
     */
    public function group(array $conditions): array
    {
        $conditions = array_values(array_filter(array_map('trim', $conditions), fn (string $c) => $c !== ''));

        if ($conditions === []) {
            return [];
        }

        $vectors = [];

        foreach (array_chunk($conditions, 64) as $chunk) {
            $part = $this->client->embed($chunk);

            if ($part === null) {
                return array_map(fn (string $c) => ['label' => $c, 'count' => 1, 'conditions' => [$c]], $conditions);
            }

            array_push($vectors, ...$part);
        }

        $threshold = (float) config('votalis.conditions.threshold', 0.86);
        /** @var list<array{members: list<int>}> $groups */
        $groups = [];

        foreach ($conditions as $i => $condition) {
            foreach ($groups as &$group) {
                $representative = $group['members'][0];

                if (EmbeddingClient::cosine($vectors[$i], $vectors[$representative]) >= $threshold) {
                    $group['members'][] = $i;

                    continue 2;
                }
            }
            unset($group);

            $groups[] = ['members' => [$i]];
        }

        $result = [];

        foreach ($groups as $group) {
            $members = $group['members'];
            $medoid = $members[0];
            $best = -INF;

            foreach ($members as $candidate) {
                $sum = 0.0;

                foreach ($members as $other) {
                    $sum += EmbeddingClient::cosine($vectors[$candidate], $vectors[$other]);
                }

                if ($sum > $best) {
                    $best = $sum;
                    $medoid = $candidate;
                }
            }

            $result[] = [
                'label' => $conditions[$medoid],
                'count' => count($members),
                'conditions' => array_map(fn (int $m) => $conditions[$m], $members),
            ];
        }

        usort($result, fn (array $a, array $b) => $b['count'] <=> $a['count']);

        return $result;
    }

    /** Recalcule et enregistre les groupes d'une proposition. */
    public function refresh(Proposal $proposal): void
    {
        /** @var list<string> $conditions */
        $conditions = $proposal->votes()->whereNotNull('condition')->pluck('condition')->all();
        $groups = $this->group($conditions);

        DB::transaction(function () use ($proposal, $groups): void {
            VoteConditionGroup::query()->where('proposal_id', $proposal->id)->delete();

            foreach ($groups as $group) {
                VoteConditionGroup::create([
                    'proposal_id' => $proposal->id,
                    'label' => $group['label'],
                    'count' => $group['count'],
                    'conditions' => $group['conditions'],
                    'computed_at' => now(),
                ]);
            }
        });
    }
}
