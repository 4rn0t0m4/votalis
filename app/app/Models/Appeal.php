<?php

namespace App\Models;

use App\Enums\AppealStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Contestation d'une décision de modération par l'auteur du contenu (ou le titulaire du compte suspendu).
 *
 * @property int $id
 * @property int $log_entry_id
 * @property int|null $author_id
 * @property string $body
 * @property AppealStatus $status
 * @property int|null $decided_by
 * @property string|null $decision_note
 * @property int|null $decision_log_entry_id
 * @property Carbon|null $decided_at
 * @property Carbon|null $created_at
 * @property-read ModerationLogEntry $logEntry
 * @property-read ModerationLogEntry|null $decisionLogEntry
 * @property-read User|null $author
 */
#[Fillable(['log_entry_id', 'author_id', 'body', 'status', 'decided_by', 'decision_note', 'decision_log_entry_id', 'decided_at'])]
class Appeal extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ModerationLogEntry, $this> */
    public function logEntry(): BelongsTo
    {
        return $this->belongsTo(ModerationLogEntry::class, 'log_entry_id');
    }

    /** @return BelongsTo<ModerationLogEntry, $this> */
    public function decisionLogEntry(): BelongsTo
    {
        return $this->belongsTo(ModerationLogEntry::class, 'decision_log_entry_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isPending(): bool
    {
        return $this->status === AppealStatus::Pending;
    }
}
