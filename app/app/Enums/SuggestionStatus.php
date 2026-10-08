<?php

namespace App\Enums;

enum SuggestionStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À examiner',
            self::Accepted => 'Ajoutée',
            self::Rejected => 'Écartée',
        };
    }
}
