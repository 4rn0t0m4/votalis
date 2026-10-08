<?php

namespace App\Enums;

enum ProposalOrigin: string
{
    case Citizen = 'citizen';
    case Seed = 'seed';

    public function label(): string
    {
        return match ($this) {
            self::Citizen => 'Contribution citoyenne',
            self::Seed => "Contenu d'amorçage",
        };
    }
}
