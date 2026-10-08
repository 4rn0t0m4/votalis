<?php

namespace App\Enums;

/** Réponse à l'une des deux questions du vote : oui, non, je ne sais pas (CDC 4.3). */
enum VoteValue: int
{
    case Yes = 1;
    case Unsure = 0;
    case No = -1;

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Oui',
            self::Unsure => 'Je ne sais pas',
            self::No => 'Non',
        };
    }
}
