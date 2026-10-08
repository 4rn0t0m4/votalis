<?php

namespace App\Enums;

/** Signaux d'intégrité (CDC section 7) : calculés chaque nuit, présentés aux modérateurs, jamais appliqués. */
enum SignalType: string
{
    case RegistrationSpike = 'registration_spike';
    case VoteSpike = 'vote_spike';
    case IdenticalVoting = 'identical_voting';
    case NearDuplicateContent = 'near_duplicate_content';
    case AtypicalHours = 'atypical_hours';

    public function label(): string
    {
        return match ($this) {
            self::RegistrationSpike => 'Pic d’inscriptions',
            self::VoteSpike => 'Pic de votes sur une proposition',
            self::IdenticalVoting => 'Comptes récents votant de manière quasi identique',
            self::NearDuplicateContent => 'Propositions presque identiques de comptes différents',
            self::AtypicalHours => 'Activité concentrée sur des heures atypiques',
        };
    }
}
