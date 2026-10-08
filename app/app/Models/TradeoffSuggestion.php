<?php

namespace App\Models;

use App\Enums\SuggestionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tradeoff_id
 * @property int|null $participant_id
 * @property int $proposal_id
 * @property string|null $note
 * @property SuggestionStatus $status
 * @property-read Tradeoff $tradeoff
 * @property-read Proposal $proposal
 * @property-read User|null $participant
 */
#[Fillable(['tradeoff_id', 'participant_id', 'proposal_id', 'note', 'status'])]
class TradeoffSuggestion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => SuggestionStatus::class];
    }

    /** @return BelongsTo<Tradeoff, $this> */
    public function tradeoff(): BelongsTo
    {
        return $this->belongsTo(Tradeoff::class);
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
}
