<?php

namespace App\Models;

use App\Enums\ArgumentSide;
use App\Enums\ArgumentStatus;
use Database\Factories\ArgumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $proposal_id
 * @property int|null $author_id
 * @property ArgumentSide $side
 * @property string $body
 * @property string|null $source_url
 * @property ArgumentStatus $status
 * @property Carbon|null $created_at
 * @property-read int|null $marks_count
 * @property-read Proposal $proposal
 * @property-read User|null $author
 * @property-read Collection<int, User> $markedBy
 */
#[Fillable(['proposal_id', 'author_id', 'side', 'body', 'source_url', 'status'])]
class Argument extends Model
{
    /** @use HasFactory<ArgumentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'side' => ArgumentSide::class,
            'status' => ArgumentStatus::class,
        ];
    }

    /** @return BelongsTo<Proposal, $this> */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Participants ayant marqué l'argument « utile ».
     *
     * @return BelongsToMany<User, $this>
     */
    public function markedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'argument_marks', 'argument_id', 'participant_id');
    }

    /** @param  Builder<Argument>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArgumentStatus::Published);
    }

    public function authorName(): string
    {
        return $this->author->pseudonym ?? 'Participant supprimé';
    }
}
