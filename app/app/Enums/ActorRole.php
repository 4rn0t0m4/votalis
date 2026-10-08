<?php

namespace App\Enums;

/** Qui a agi, tel qu'affiché dans le journal public : jamais un pseudonyme. */
enum ActorRole: string
{
    case System = 'system';
    case Moderator = 'moderator';
    case Editorial = 'editorial';

    public function label(): string
    {
        return match ($this) {
            self::System => 'Automatique',
            self::Moderator => 'Modération',
            self::Editorial => 'Comité éditorial',
        };
    }

    public static function fromRole(Role $role): self
    {
        return $role === Role::Editorial ? self::Editorial : self::Moderator;
    }
}
