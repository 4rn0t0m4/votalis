<?php

namespace App\Enums;

enum ThemeStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouvert',
            self::Closed => 'Fermé aux nouvelles propositions',
            self::Archived => 'Archivé',
        };
    }
}
