<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\EmailHasher;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * @property int $id
 * @property string $pseudonym
 * @property string $email E-mail déchiffré à la lecture (cast `encrypted`)
 * @property string $email_hash
 * @property Role $role
 * @property Carbon|null $email_verified_at
 * @property Carbon $consented_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $suspended_until
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $inactivity_notice_sent_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property-read Collection<int, Vote> $votes
 * @property-read Collection<int, TradeoffAnswer> $tradeoffAnswers
 */
#[Fillable(['pseudonym', 'email', 'password', 'consented_at'])]
#[Hidden(['password', 'remember_token', 'email', 'email_hash', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty('email')) {
                $user->email_hash = EmailHasher::hash($user->email);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'email_verified_at' => 'datetime',
            'consented_at' => 'datetime',
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
            'last_seen_at' => 'date',
            'inactivity_notice_sent_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /** @return HasMany<Proposal, $this> */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'author_id');
    }

    /** @return HasMany<Argument, $this> */
    public function arguments(): HasMany
    {
        return $this->hasMany(Argument::class, 'author_id');
    }

    /** @return HasMany<TradeoffAnswer, $this> */
    public function tradeoffAnswers(): HasMany
    {
        return $this->hasMany(TradeoffAnswer::class, 'participant_id');
    }

    /** @return HasMany<Vote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'participant_id');
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /**
     * Arguments marqués « utile ».
     *
     * @return BelongsToMany<Argument, $this>
     */
    public function markedArguments(): BelongsToMany
    {
        return $this->belongsToMany(Argument::class, 'argument_marks', 'participant_id', 'argument_id');
    }

    /**
     * Jalons du parcours personnel : lecture seule, jamais chargés dans une vue publique.
     *
     * @return HasMany<UserMilestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(UserMilestone::class, 'participant_id');
    }

    /** @return HasMany<Appeal, $this> */
    public function appeals(): HasMany
    {
        return $this->hasMany(Appeal::class, 'author_id');
    }

    /** Compte suspendu par le comité éditorial : lecture et contestation seulement. */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null && ($this->suspended_until === null || $this->suspended_until->isFuture());
    }

    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }

    /** La double authentification est-elle exigée pour ce compte ? */
    public function requiresTwoFactor(): bool
    {
        return $this->role->isPrivileged();
    }

    /** Un second facteur (TOTP confirmé ou clé WebAuthn) est-il actif ? */
    public function hasSecondFactor(): bool
    {
        return $this->hasEnabledTwoFactorAuthentication() || $this->hasPasskeysEnabled();
    }

    /**
     * La table des jetons de réinitialisation stocke le haché, jamais l'e-mail en clair.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email_hash;
    }

    /** Les authentificateurs n'affichent que le pseudonyme, jamais l'e-mail. */
    public function getPasskeyDisplayName(): string
    {
        return $this->pseudonym;
    }

    public function getPasskeyUsername(): string
    {
        return $this->pseudonym;
    }
}
