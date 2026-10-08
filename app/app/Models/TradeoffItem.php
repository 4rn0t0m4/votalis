<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tradeoff_id
 * @property int $proposal_id
 * @property string $impact
 * @property string $uncertainty
 * @property string $source_url
 * @property int $position
 * @property-read Tradeoff $tradeoff
 * @property-read Proposal $proposal
 */
#[Fillable(['tradeoff_id', 'proposal_id', 'impact', 'uncertainty', 'source_url', 'position'])]
class TradeoffItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['impact' => 'decimal:2'];
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

    public function impactValue(): float
    {
        return (float) $this->impact;
    }
}
