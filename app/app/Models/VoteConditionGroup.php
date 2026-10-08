<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $proposal_id
 * @property string $label
 * @property int $count
 * @property list<string> $conditions
 * @property Carbon $computed_at
 */
#[Fillable(['proposal_id', 'label', 'count', 'conditions', 'computed_at'])]
class VoteConditionGroup extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['conditions' => 'array', 'computed_at' => 'datetime'];
    }

    /** @return BelongsTo<Proposal, $this> */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
