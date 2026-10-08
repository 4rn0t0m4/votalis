<?php

namespace App\Models;

use App\Enums\ActorRole;
use App\Enums\ModerationAction;
use App\Enums\ReportMotive;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Entrée du journal public de modération. En ajout seul : la base refuse UPDATE,
 * DELETE et TRUNCATE (déclencheur), et ce modèle refuse de les tenter.
 *
 * @property int $id
 * @property string $target_type
 * @property int $target_id
 * @property ModerationAction $action
 * @property ReportMotive|null $motive
 * @property int|null $actor_id
 * @property ActorRole $actor_role
 * @property array<string, mixed>|null $details
 * @property Carbon $created_at
 * @property-read Proposal|Argument|null $target
 */
#[Fillable(['target_type', 'target_id', 'action', 'motive', 'actor_id', 'actor_role', 'details'])]
class ModerationLogEntry extends Model
{
    protected $table = 'moderation_log';

    public const ?string UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ModerationAction::class,
            'motive' => ReportMotive::class,
            'actor_role' => ActorRole::class,
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Une entrée du journal de modération ne se modifie pas.');
        }

        return parent::save($options);
    }

    public function delete(): bool
    {
        throw new LogicException('Une entrée du journal de modération ne se supprime pas.');
    }

    /** Le journal ne montre jamais le contenu masqué pour contenu illégal (CDC section 6). */
    public function showsTarget(): bool
    {
        return $this->motive?->hidesContentInLog() !== true;
    }

    public function targetLabel(): string
    {
        return $this->target_type === 'proposal' ? 'Proposition' : 'Argument';
    }

    public function url(): string
    {
        return route('moderation-log.show', $this);
    }
}
