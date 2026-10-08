<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Enums\SuggestionStatus;
use App\Enums\TradeoffStatus;
use App\Models\Proposal;
use App\Models\Tradeoff;
use App\Models\TradeoffAnswer;
use App\Models\TradeoffAnswerRevision;
use App\Models\TradeoffItem;
use App\Models\TradeoffSuggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Arbitrages (CDC 4.9) : composer une combinaison de mesures chiffrées sous contrainte.
 * Toutes les règles sont appliquées ici, côté serveur : exercice ouvert, mesures de l'exercice,
 * contrainte atteinte, conditions, remplacement de la réponse avec historique.
 */
class TradeoffService
{
    public const CONDITION_MAX = 200;

    public const MIN_ITEMS_TO_OPEN = 2;

    /**
     * @param  list<int>  $itemIds
     * @param  array<int|string, string>  $conditions  item_id → « acceptée à condition que… »
     *
     * @throws ValidationException
     */
    public function answer(User $user, Tradeoff $tradeoff, array $itemIds, array $conditions = []): TradeoffAnswer
    {
        app(ReadOnlyMode::class)->assertWritable();

        if (! $tradeoff->isOpen()) {
            throw ValidationException::withMessages(['tradeoff' => [__('Cet arbitrage n’est pas ouvert.')]]);
        }

        if (! $user->can('participate') || ! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['tradeoff' => [__('Seuls les participants vérifiés peuvent arbitrer.')]]);
        }

        $items = $tradeoff->items()->get()->keyBy('id');
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));

        foreach ($itemIds as $id) {
            if (! $items->has($id)) {
                throw ValidationException::withMessages(['items' => [__('Une des mesures choisies n’appartient pas à cet arbitrage.')]]);
            }
        }

        $total = round(array_sum(array_map(fn (int $id) => $items->get($id)?->impactValue() ?? 0.0, $itemIds)), 2);

        if (! $tradeoff->satisfiedBy($total)) {
            throw ValidationException::withMessages(['items' => [__('La contrainte n’est pas atteinte : :total pour un objectif « :direction :target ».', [
                'total' => $tradeoff->formatAmount($total),
                'direction' => mb_strtolower($tradeoff->direction->label()),
                'target' => $tradeoff->formatAmount($tradeoff->target()),
            ])]]);
        }

        $cleanConditions = [];

        foreach ($conditions as $itemId => $text) {
            $text = trim((string) $text);

            if ($text === '') {
                continue;
            }

            if (! in_array((int) $itemId, $itemIds, true)) {
                throw ValidationException::withMessages(['conditions' => [__('Une condition porte sur une mesure non choisie.')]]);
            }

            if (mb_strlen($text) > self::CONDITION_MAX) {
                throw ValidationException::withMessages(['conditions' => [__('Une condition ne doit pas dépasser :max caractères.', ['max' => self::CONDITION_MAX])]]);
            }

            $cleanConditions[(int) $itemId] = $text;
        }

        sort($itemIds);

        return DB::transaction(function () use ($user, $tradeoff, $itemIds, $cleanConditions, $total): TradeoffAnswer {
            $answer = TradeoffAnswer::query()->where('participant_id', $user->id)->where('tradeoff_id', $tradeoff->id)->lockForUpdate()->first();

            $attributes = ['item_ids' => $itemIds, 'conditions' => $cleanConditions, 'total' => $total];

            if ($answer === null) {
                $answer = TradeoffAnswer::create(['participant_id' => $user->id, 'tradeoff_id' => $tradeoff->id] + $attributes);
            } else {
                $answer->fill($attributes)->save();
            }

            TradeoffAnswerRevision::create(['participant_id' => $user->id, 'tradeoff_id' => $tradeoff->id] + $attributes);
            Cache::forget("tradeoff-results:{$tradeoff->id}");

            return $answer;
        });
    }

    public function answerOf(?User $user, Tradeoff $tradeoff): ?TradeoffAnswer
    {
        return $user === null ? null : TradeoffAnswer::query()->where('participant_id', $user->id)->where('tradeoff_id', $tradeoff->id)->first();
    }

    /**
     * @return Collection<int, TradeoffAnswerRevision>
     */
    public function historyOf(User $user, Tradeoff $tradeoff): Collection
    {
        return TradeoffAnswerRevision::query()->where('participant_id', $user->id)->where('tradeoff_id', $tradeoff->id)->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    /**
     * Résultats publics (CDC 4.9) : fréquence de choix par mesure, combinaisons les plus fréquentes, conditions les plus citées.
     *
     * @return array{participants: int, items: list<array{item: TradeoffItem, count: int, percent: int, conditions: list<array{text: string, count: int}>}>, combinations: list<array{item_ids: list<int>, count: int, percent: int, total: float}>}
     */
    public function results(Tradeoff $tradeoff): array
    {
        /** @var array{participants: int, items: list<array{item: TradeoffItem, count: int, percent: int, conditions: list<array{text: string, count: int}>}>, combinations: list<array{item_ids: list<int>, count: int, percent: int, total: float}>} */
        return Cache::remember("tradeoff-results:{$tradeoff->id}", now()->addSeconds((int) config('votalis.rankings.cache_seconds', 300)), function () use ($tradeoff): array {
            $answers = $tradeoff->answers()->get();
            $participants = $answers->count();
            $items = $tradeoff->items()->with('proposal')->get();

            $itemResults = [];

            foreach ($items as $item) {
                $count = $answers->filter(fn (TradeoffAnswer $a) => in_array($item->id, $a->item_ids, true))->count();
                /** @var array<string, array{text: string, count: int}> $conditionCounts */
                $conditionCounts = [];

                foreach ($answers as $answer) {
                    $text = $answer->conditions[$item->id] ?? null;

                    if (is_string($text) && $text !== '') {
                        $key = mb_strtolower(trim($text));
                        $conditionCounts[$key] = ['text' => $conditionCounts[$key]['text'] ?? trim($text), 'count' => ($conditionCounts[$key]['count'] ?? 0) + 1];
                    }
                }

                usort($conditionCounts, fn (array $a, array $b) => $b['count'] <=> $a['count']);

                $itemResults[] = [
                    'item' => $item,
                    'count' => $count,
                    'percent' => VoteService::percent($count, $participants),
                    'conditions' => array_slice($conditionCounts, 0, 5),
                ];
            }

            usort($itemResults, fn (array $a, array $b) => $b['count'] <=> $a['count']);

            /** @var array<string, array{item_ids: list<int>, count: int, percent: int, total: float}> $combinations */
            $combinations = [];

            foreach ($answers as $answer) {
                $key = implode('-', $answer->item_ids);
                $combinations[$key] = [
                    'item_ids' => $answer->item_ids,
                    'count' => ($combinations[$key]['count'] ?? 0) + 1,
                    'percent' => 0,
                    'total' => (float) $answer->total,
                ];
            }

            usort($combinations, fn (array $a, array $b) => $b['count'] <=> $a['count']);
            $combinations = array_slice($combinations, 0, 5);

            foreach ($combinations as &$combination) {
                $combination['percent'] = VoteService::percent($combination['count'], $participants);
            }
            unset($combination);

            return ['participants' => $participants, 'items' => $itemResults, 'combinations' => $combinations];
        });
    }

    /**
     * Ajout d'une mesure candidate par le comité : chiffrage et source obligatoires (C7).
     *
     * @param  array{proposal_id: int, impact: float|string, uncertainty: string, source_url: string, position?: int}  $data
     *
     * @throws ValidationException
     */
    public function addItem(Tradeoff $tradeoff, array $data): TradeoffItem
    {
        $proposal = Proposal::query()->find($data['proposal_id']);

        if ($proposal === null || $proposal->status !== ProposalStatus::Published) {
            throw ValidationException::withMessages(['proposal_id' => [__('Choisissez une proposition publiée.')]]);
        }

        if (trim((string) $data['source_url']) === '' || ! filter_var($data['source_url'], FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages(['source_url' => [__('Le chiffrage doit être sourcé : indiquez l’adresse de la source.')]]);
        }

        if (! is_numeric($data['impact'])) {
            throw ValidationException::withMessages(['impact' => [__('Indiquez l’impact estimé, en :unit.', ['unit' => $tradeoff->unit])]]);
        }

        if (trim((string) $data['uncertainty']) === '') {
            throw ValidationException::withMessages(['uncertainty' => [__('Indiquez l’incertitude du chiffrage (ex. « ± 20 % », « ordre de grandeur »).')]]);
        }

        if ($tradeoff->items()->where('proposal_id', $proposal->id)->exists()) {
            throw ValidationException::withMessages(['proposal_id' => [__('Cette proposition figure déjà dans l’arbitrage.')]]);
        }

        $item = $tradeoff->items()->create([
            'proposal_id' => $proposal->id,
            'impact' => round((float) $data['impact'], 2),
            'uncertainty' => trim((string) $data['uncertainty']),
            'source_url' => trim((string) $data['source_url']),
            'position' => (int) ($data['position'] ?? ($tradeoff->items()->max('position') + 1)),
        ]);

        TradeoffSuggestion::query()->where('tradeoff_id', $tradeoff->id)->where('proposal_id', $proposal->id)->update(['status' => SuggestionStatus::Accepted]);
        Cache::forget("tradeoff-results:{$tradeoff->id}");

        return $item;
    }

    /**
     * @throws ValidationException
     */
    public function changeStatus(Tradeoff $tradeoff, TradeoffStatus $status): void
    {
        if ($status === TradeoffStatus::Open && $tradeoff->items()->count() < self::MIN_ITEMS_TO_OPEN) {
            throw ValidationException::withMessages(['status' => [__('Un arbitrage s’ouvre avec au moins :min mesures chiffrées.', ['min' => self::MIN_ITEMS_TO_OPEN])]]);
        }

        $tradeoff->update(['status' => $status]);
    }

    /**
     * @throws ValidationException
     */
    public function suggest(User $user, Tradeoff $tradeoff, int $proposalId, ?string $note): TradeoffSuggestion
    {
        if (! $tradeoff->isOpen()) {
            throw ValidationException::withMessages(['tradeoff' => [__('Cet arbitrage n’est pas ouvert.')]]);
        }

        if (! $user->can('participate') || ! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['tradeoff' => [__('Seuls les participants vérifiés peuvent proposer une mesure.')]]);
        }

        $proposal = Proposal::query()->published()->find($proposalId);

        if ($proposal === null) {
            throw ValidationException::withMessages(['proposal_id' => [__('Choisissez une proposition publiée.')]]);
        }

        if ($tradeoff->items()->where('proposal_id', $proposal->id)->exists() || $tradeoff->suggestions()->where('proposal_id', $proposal->id)->exists()) {
            throw ValidationException::withMessages(['proposal_id' => [__('Cette proposition est déjà dans l’arbitrage ou déjà proposée.')]]);
        }

        $note = trim((string) $note);

        if (mb_strlen($note) > 500) {
            throw ValidationException::withMessages(['note' => [__('La note ne doit pas dépasser 500 caractères.')]]);
        }

        return $tradeoff->suggestions()->create([
            'participant_id' => $user->id,
            'proposal_id' => $proposal->id,
            'note' => $note ?: null,
        ]);
    }
}
