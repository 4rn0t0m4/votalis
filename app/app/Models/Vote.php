<?php

namespace App\Models;

use App\Enums\VoteValue;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $participant_id
 * @property int $proposal_id
 * @property VoteValue $desirable
 * @property VoteValue $necessary
 * @property string|null $condition
 * @property VoteValue $desirable_initial
 * @property VoteValue $necessary_initial
 * @property bool $revised_after_arguments
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Proposal $proposal
 * @property-read User $participant
 */
#[Fillable(['participant_id', 'proposal_id', 'desirable', 'necessary', 'condition', 'desirable_initial', 'necessary_initial', 'revised_after_arguments', 'after_arguments'])]
class Vote extends Model
{
    public $incrementing = false;

    protected $primaryKey = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'desirable' => VoteValue::class,
            'necessary' => VoteValue::class,
            'desirable_initial' => VoteValue::class,
            'necessary_initial' => VoteValue::class,
            'revised_after_arguments' => 'boolean',
            'after_arguments' => 'boolean',
        ];
    }

    /** @return BelongsTo<Proposal, $this> */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_id');
    }

    public function isConditional(): bool
    {
        return $this->condition !== null && $this->condition !== '';
    }

    /** Clé composite : Eloquent a besoin d'une requête ciblée pour sauvegarder. */
    protected function setKeysForSaveQuery($query)
    {
        return $query->where('participant_id', $this->participant_id)->where('proposal_id', $this->proposal_id);
    }
}
