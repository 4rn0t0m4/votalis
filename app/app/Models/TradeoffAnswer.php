<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $participant_id
 * @property int $tradeoff_id
 * @property list<int> $item_ids
 * @property array<int|string, string> $conditions
 * @property string $total
 * @property Carbon|null $updated_at
 * @property-read Tradeoff $tradeoff
 */
#[Fillable(['participant_id', 'tradeoff_id', 'item_ids', 'conditions', 'total'])]
class TradeoffAnswer extends Model
{
    public $incrementing = false;

    protected $primaryKey = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['item_ids' => 'array', 'conditions' => 'array', 'total' => 'decimal:2'];
    }

    /** @return BelongsTo<Tradeoff, $this> */
    public function tradeoff(): BelongsTo
    {
        return $this->belongsTo(Tradeoff::class);
    }

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('participant_id', $this->participant_id)->where('tradeoff_id', $this->tradeoff_id);
    }
}
