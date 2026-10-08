<?php

namespace App\Models;

use App\Enums\ProposalOrigin;
use App\Enums\ProposalStatus;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $theme_id
 * @property int|null $author_id
 * @property int|null $family_id
 * @property int|null $parent_id
 * @property string $title
 * @property string $problem
 * @property string $measure
 * @property string|null $cost_estimate
 * @property bool $cost_unknown
 * @property ProposalOrigin $origin
 * @property string|null $seed_source
 * @property ProposalStatus $status
 * @property Carbon|null $content_locked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Theme $theme
 * @property-read User|null $author
 * @property-read Collection<int, ProposalSource> $sources
 * @property-read Collection<int, ProposalRevision> $revisions
 * @property-read Collection<int, Argument> $arguments
 */
#[Fillable(['theme_id', 'author_id', 'title', 'problem', 'measure', 'cost_estimate', 'cost_unknown', 'origin', 'seed_source', 'status', 'content_locked_at'])]
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_unknown' => 'boolean',
            'origin' => ProposalOrigin::class,
            'status' => ProposalStatus::class,
            'content_locked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Theme, $this> */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return HasMany<ProposalSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(ProposalSource::class);
    }

    /** @return HasMany<ProposalRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(ProposalRevision::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    /** @return HasMany<Argument, $this> */
    public function arguments(): HasMany
    {
        return $this->hasMany(Argument::class);
    }

    /** @param  Builder<Proposal>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProposalStatus::Published);
    }

    public function isLocked(): bool
    {
        return $this->content_locked_at !== null;
    }

    public function slug(): string
    {
        return Str::slug($this->title) ?: 'proposition';
    }

    public function url(): string
    {
        return route('proposals.show', ['proposal' => $this->id, 'slug' => $this->slug()]);
    }

    /** Nom public de l'auteur : pseudonyme, ou mention neutre pour un amorçage ou un compte supprimé. */
    public function authorName(): string
    {
        if ($this->origin === ProposalOrigin::Seed) {
            return 'Comité éditorial';
        }

        return $this->author->pseudonym ?? 'Participant supprimé';
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'title' => $this->title,
            'problem' => $this->problem,
            'measure' => $this->measure,
            'cost_estimate' => $this->cost_estimate,
            'cost_unknown' => $this->cost_unknown,
            'sources' => $this->sources->map(fn (ProposalSource $s) => $s->is_personal ? 'Proposition personnelle' : $s->url)->values()->all(),
        ];
    }
}
