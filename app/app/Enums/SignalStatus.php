<?php

namespace App\Enums;

enum SignalStatus: string
{
    case New = 'new';
    case Reviewed = 'reviewed';
    case Confirmed = 'confirmed';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Reviewed => 'Examiné',
            self::Confirmed => 'Opération coordonnée confirmée',
            self::Dismissed => 'Écarté',
        };
    }
}
