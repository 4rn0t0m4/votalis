<?php

namespace App\Enums;

enum AppealStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Overturned = 'overturned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente du comité éditorial',
            self::Confirmed => 'Décision confirmée',
            self::Overturned => 'Décision annulée, contenu rétabli',
        };
    }
}
