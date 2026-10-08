<?php

namespace App\Services;

use App\Models\Proposal;
use Illuminate\Support\Collection;

/**
 * Détection de doublons au dépôt (CDC 4.5) : les fiches publiées sémantiquement les plus
 * proches d'un texte, au-dessus d'un seuil de similarité cosinus configurable.
 */
class DuplicateFinder
{
    public function __construct(private readonly EmbeddingClient $client) {}

    public static function text(string $title, string $measure): string
    {
        return trim($title)."\n\n".trim($measure);
    }

    public function enoughText(string $title, string $measure): bool
    {
        return mb_strlen(trim($title)) >= (int) config('votalis.duplicates.min_title_chars', 10)
            && mb_strlen(trim($measure)) >= (int) config('votalis.duplicates.min_measure_chars', 30);
    }

    /**
     * @param  list<int>  $excludeIds
     * @return Collection<int, Proposal> Propositions avec un attribut `similarity` (0 à 1)
     */
    public function similarTo(string $title, string $measure, array $excludeIds = []): Collection
    {
        $vector = $this->client->embedOne(self::text($title, $measure));

        if ($vector === null) {
            return collect();
        }

        return $this->similarToVector($vector, $excludeIds);
    }

    /**
     * @param  list<float>  $vector
     * @param  list<int>  $excludeIds
     * @return Collection<int, Proposal>
     */
    public function similarToVector(array $vector, array $excludeIds = []): Collection
    {
        $literal = EmbeddingClient::literal($vector);
        $threshold = (float) config('votalis.duplicates.threshold', 0.84);
        $limit = (int) config('votalis.duplicates.limit', 5);

        /** @var Collection<int, Proposal> $results */
        $results = Proposal::query()
            ->published()
            ->whereNotNull('embedding')
            ->when($excludeIds !== [], fn ($q) => $q->whereKeyNot($excludeIds))
            ->with('theme')
            ->selectRaw('proposals.*, 1 - (embedding <=> ?::vector) as similarity', [$literal])
            ->orderByRaw('embedding <=> ?::vector', [$literal])
            ->limit($limit)
            ->get()
            ->filter(fn (Proposal $p) => (float) $p->getAttribute('similarity') >= $threshold)
            ->values();

        return $results;
    }
}
