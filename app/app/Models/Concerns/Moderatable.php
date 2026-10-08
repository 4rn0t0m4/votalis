<?php

namespace App\Models\Concerns;

use App\Enums\ReportMotive;
use App\Models\ModerationLogEntry;
use App\Models\Report;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Contenu signalable et modérable (proposition, argument). Les deux modèles
 * portent `status`, `hidden_motive` et `author_id`.
 *
 * @property ReportMotive|null $hidden_motive
 */
trait Moderatable
{
    /** @return MorphMany<Report, $this> */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'target');
    }

    /** @return MorphMany<ModerationLogEntry, $this> */
    public function moderationEntries(): MorphMany
    {
        return $this->morphMany(ModerationLogEntry::class, 'target')->orderByDesc('id');
    }

    public function isPublished(): bool
    {
        return $this->status->value === 'published';
    }

    /** Masqué par la modération, définitivement ou en attente de décision. */
    public function isHidden(): bool
    {
        return $this->status->value === 'hidden';
    }

    public function isModerated(): bool
    {
        return ! $this->isPublished();
    }

    /** Le motif « contenu illégal » interdit d'afficher le moindre extrait, même le titre. */
    public function hidesEverything(): bool
    {
        return $this->isModerated() && $this->hidden_motive?->hidesContentInLog() === true;
    }
}
