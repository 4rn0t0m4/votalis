<?php

namespace App\Enums;

enum RevisionKind: string
{
    case Content = 'content';
    case Typo = 'typo';

    public function label(): string
    {
        return match ($this) {
            self::Content => 'Rédaction',
            self::Typo => 'Correction de forme',
        };
    }
}
