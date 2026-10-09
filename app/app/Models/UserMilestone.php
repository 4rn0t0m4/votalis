<?php

namespace App\Models;

use App\Enums\Milestone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Jalon atteint par un participant (table `milestones`, clé composée). Lecture seule depuis
 * l'application : l'écriture passe par `App\Services\Journey`. Jamais exposé publiquement.
 *
 * @property int $participant_id
 * @property Milestone $key
 * @property Carbon $reached_at
 * @property Carbon|null $seen_at
 */
class UserMilestone extends Model
{
    protected $table = 'milestones';

    public $timestamps = false;

    public $incrementing = false;

    /** @var list<string> */
    protected $hidden = ['participant_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['key' => Milestone::class, 'reached_at' => 'datetime', 'seen_at' => 'datetime'];
    }

    /** Clé composée : aucune sauvegarde par le modèle. */
    public function save(array $options = []): bool
    {
        throw new \LogicException('Les jalons s’écrivent par App\Services\Journey.');
    }
}
