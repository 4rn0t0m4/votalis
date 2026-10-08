<?php

namespace App\Models;

use App\Enums\TradeoffDirection;
use App\Enums\TradeoffStatus;
use Database\Factories\TradeoffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $objective
 * @property string $constraint_value
 * @property string $unit
 * @property TradeoffDirection $direction
 * @property TradeoffStatus $status
 * @property string|null $source_url
 * @property string|null $source_label
 * @property int|null $theme_id
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read Theme|null $theme
 * @property-read Collection<int, TradeoffItem> $items
 * @property-read Collection<int, TradeoffAnswer> $answers
 * @property-read Collection<int, TradeoffSuggestion> $suggestions
 * @property-read int|null $answers_count
 */
#[Fillable(['title', 'slug', 'objective', 'constraint_value', 'unit', 'direction', 'status', 'source_url', 'source_label', 'theme_id', 'created_by'])]
class Tradeoff extends Model
{
    /** @use HasFactory<TradeoffFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => TradeoffDirection::class,
            'status' => TradeoffStatus::class,
            'constraint_value' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Theme, $this> */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /** @return HasMany<TradeoffItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(TradeoffItem::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<TradeoffAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(TradeoffAnswer::class);
    }

    /** @return HasMany<TradeoffSuggestion, $this> */
    public function suggestions(): HasMany
    {
        return $this->hasMany(TradeoffSuggestion::class);
    }

    public function isOpen(): bool
    {
        return $this->status === TradeoffStatus::Open;
    }

    public function isPublic(): bool
    {
        return $this->status !== TradeoffStatus::Draft;
    }

    public function target(): float
    {
        return (float) $this->constraint_value;
    }

    public function satisfiedBy(float $total): bool
    {
        return $this->direction->isSatisfied($total, $this->target());
    }

    public function formatAmount(float $value): string
    {
        return number_format($value, (floor($value) === $value) ? 0 : 1, ',', ' ').' '.$this->unit;
    }
}
