<?php

namespace App\Enums;

/**
 * Les cinq rôles du cahier des charges (section 3). Le visiteur n'a pas de compte.
 * Les rôles sont cumulatifs de participant à comité éditorial ; l'administrateur
 * technique n'a aucun pouvoir éditorial.
 */
enum Role: string
{
    case Participant = 'participant';
    case Moderator = 'moderator';
    case Editorial = 'editorial';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Participant => 'Participant vérifié',
            self::Moderator => 'Modérateur',
            self::Editorial => 'Comité éditorial',
            self::Admin => 'Administrateur technique',
        };
    }

    /** Rôles pour lesquels la double authentification est obligatoire. */
    public function isPrivileged(): bool
    {
        return $this !== self::Participant;
    }

    /**
     * Capacités accordées à ce rôle.
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Participant => ['participate'],
            self::Moderator => ['participate', 'moderate'],
            self::Editorial => ['participate', 'moderate', 'manage-themes', 'manage-tradeoffs', 'publish-synthesis', 'arbitrate-appeals'],
            self::Admin => ['manage-platform'],
        };
    }

    /**
     * Toutes les capacités connues, pour déclarer les Gates.
     *
     * @return list<string>
     */
    public static function allAbilities(): array
    {
        return array_values(array_unique(array_merge(...array_map(fn (self $r) => $r->abilities(), self::cases()))));
    }
}
