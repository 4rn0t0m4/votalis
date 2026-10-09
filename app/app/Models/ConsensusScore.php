<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Score de consensus d'une proposition pour un calcul donné. `score` est le taux d'accord lissé
 * du groupe le moins favorable ; `divisiveness` l'écart entre le plus et le moins favorable.
 *
 * @property int $run_id
 * @property int $proposal_id
 * @property string|null $score
 * @property string|null $divisiveness
 * @property list<array{label: string, voters: int, agree_rate: float, necessary_rate: float, represented: bool}> $groups
 */
class ConsensusScore extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'proposal_id';

    protected $guarded = [];

    /** @return BelongsTo<ConsensusRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(ConsensusRun::class, 'run_id');
    }

    /** @return BelongsTo<Proposal, $this> */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /** Groupes représentés (assez de votants sur la fiche), seuls pris en compte dans le score. */
    public function representedGroups(): int
    {
        return count(array_filter($this->groups, fn (array $g) => $g['represented']));
    }

    protected function casts(): array
    {
        return ['groups' => 'array'];
    }
}
