<?php

namespace App\Enums;

enum ArgumentSide: string
{
    case For = 'for';
    case Against = 'against';

    public function label(): string
    {
        return match ($this) {
            self::For => 'Pour',
            self::Against => 'Contre',
        };
    }
}
