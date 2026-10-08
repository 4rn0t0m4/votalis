<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\EmailHasher;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
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
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
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
