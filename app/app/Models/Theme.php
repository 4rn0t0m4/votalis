<?php

namespace App\Models;

use App\Enums\ThemeStatus;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property ThemeStatus $status
 * @property int $position
 * @property-read Theme|null $parent
 * @property-read Collection<int, Theme> $children
 * @property-read Collection<int, Proposal> $proposals
 */
#[Fillable(['parent_id', 'name', 'slug', 'description', 'status', 'position'])]
class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => ThemeStatus::class];
    }

    /** @return BelongsTo<Theme, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'parent_id');
    }

    /** @return HasMany<Theme, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Theme::class, 'parent_id')->orderBy('position')->orderBy('name');
    }

    /** @return HasMany<Proposal, $this> */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * Thèmes visibles dans les listes (les archivés restent accessibles par URL).
     *
     * @param  Builder<Theme>  $query
     */
    public function scopeListed(Builder $query): void
    {
        $query->where('status', '!=', ThemeStatus::Archived);
    }

    /** @param  Builder<Theme>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', ThemeStatus::Open);
    }

    /** @param  Builder<Theme>  $query */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function acceptsProposals(): bool
    {
        return $this->status === ThemeStatus::Open;
    }

    public function isArchived(): bool
    {
        return $this->status === ThemeStatus::Archived;
    }

    /** Nom complet « Parent › Enfant ». */
    public function fullName(): string
    {
        return $this->parent !== null ? $this->parent->name.' › '.$this->name : $this->name;
    }
}
