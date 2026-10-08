<?php

namespace App\Enums;

/** Sens de la contrainte : atteindre au moins la cible (économies à trouver) ou ne pas la dépasser. */
enum TradeoffDirection: string
{
    case AtLeast = 'at_least';
    case AtMost = 'at_most';

    public function label(): string
    {
        return match ($this) {
            self::AtLeast => 'Atteindre au moins',
            self::AtMost => 'Ne pas dépasser',
        };
    }

    public function isSatisfied(float $total, float $target): bool
    {
        return $this === self::AtLeast ? $total >= $target : $total <= $target;
    }
}
