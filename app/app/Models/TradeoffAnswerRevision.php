<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $participant_id
 * @property int $tradeoff_id
 * @property list<int> $item_ids
 * @property array<int|string, string> $conditions
 * @property string $total
 * @property Carbon $created_at
 */
#[Fillable(['participant_id', 'tradeoff_id', 'item_ids', 'conditions', 'total'])]
class TradeoffAnswerRevision extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['item_ids' => 'array', 'conditions' => 'array', 'total' => 'decimal:2', 'created_at' => 'datetime'];
    }
}
