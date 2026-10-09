<?php

namespace App\Models;

use App\Enums\ConsensusRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Un calcul de consensus (lot 7). Ne contient aucune donnée de compte.
 *
 * @property int $id
 * @property ConsensusRunStatus $status
 * @property string|null $algo_version
 * @property int $participants
 * @property int $proposals
 * @property int $k
 * @property string|null $silhouette
 * @property list<array{label: string, size: int}>|null $groups
 * @property array<string, int|float> $params
 * @property string|null $input_digest
 * @property string|null $error
 * @property Carbon $computed_at
 */
class ConsensusRun extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /** @return HasMany<ConsensusScore, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(ConsensusScore::class, 'run_id');
    }

    protected function casts(): array
    {
        return [
            'status' => ConsensusRunStatus::class,
            'groups' => 'array',
            'params' => 'array',
            'computed_at' => 'datetime',
        ];
    }
}
