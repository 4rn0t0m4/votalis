<?php

namespace App\Services;

/**
 * Part de texte modifiée entre deux versions : distance d'édition (Levenshtein,
 * en caractères Unicode) rapportée à la longueur de la plus longue version.
 */
final class TextSimilarity
{
    public static function changeRatio(string $before, string $after): float
    {
        $max = max(mb_strlen($before), mb_strlen($after));

        if ($max === 0) {
            return 0.0;
        }

        return self::levenshtein($before, $after) / $max;
    }

    public static function levenshtein(string $a, string $b): int
    {
        $x = mb_str_split($a);
        $y = mb_str_split($b);
        $n = count($x);
        $m = count($y);

        if ($n === 0) {
            return $m;
        }

        if ($m === 0) {
            return $n;
        }

        $previous = range(0, $m);

        for ($i = 1; $i <= $n; $i++) {
            $current = [$i];

            for ($j = 1; $j <= $m; $j++) {
                $cost = $x[$i - 1] === $y[$j - 1] ? 0 : 1;
                $current[$j] = min($previous[$j] + 1, $current[$j - 1] + 1, $previous[$j - 1] + $cost);
            }

            $previous = $current;
        }

        return $previous[$m];
    }
}
