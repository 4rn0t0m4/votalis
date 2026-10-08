<?php

namespace App\Models;

use App\Enums\ReportMotive;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Signalement d'un contenu par un participant (CDC 4.8). L'identité du signaleur
 * n'est jamais montrée, ni à l'auteur ni aux modérateurs : seul son historique agrégé l'est.
 *
 * @property int $id
 * @property string $target_type
 * @property int $target_id
 * @property int|null $reporter_id
 * @property ReportMotive $motive
 * @property string|null $details
 * @property ReportStatus $status
 * @property int|null $log_entry_id
 * @property Carbon|null $created_at
 * @property-read Proposal|Argument $target
 * @property-read User|null $reporter
 * @property-read ModerationLogEntry|null $logEntry
 */
#[Fillable(['target_type', 'target_id', 'reporter_id', 'motive', 'details', 'status', 'log_entry_id'])]
class Report extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'motive' => ReportMotive::class,
            'status' => ReportStatus::class,
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return BelongsTo<ModerationLogEntry, $this> */
    public function logEntry(): BelongsTo
    {
        return $this->belongsTo(ModerationLogEntry::class, 'log_entry_id');
    }
}
