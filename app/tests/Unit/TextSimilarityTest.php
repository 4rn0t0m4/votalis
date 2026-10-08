<?php

namespace Tests\Unit;

use App\Services\TextSimilarity;
use PHPUnit\Framework\TestCase;

class TextSimilarityTest extends TestCase
{
    public function test_distance_d_edition_unicode(): void
    {
        $this->assertSame(0, TextSimilarity::levenshtein('été', 'été'));
        $this->assertSame(1, TextSimilarity::levenshtein('Plafoner', 'Plafonner'));
        $this->assertSame(3, TextSimilarity::levenshtein('', 'abc'));
    }

    public function test_une_faute_corrigee_reste_sous_le_seuil_et_une_reecriture_le_depasse(): void
    {
        $this->assertLessThan(0.10, TextSimilarity::changeRatio('Plafoner les dépassements d’honoraires', 'Plafonner les dépassements d’honoraires'));
        $this->assertGreaterThan(0.10, TextSimilarity::changeRatio('Plafonner les dépassements d’honoraires', 'Supprimer le secteur 2 pour tous les médecins'));
        $this->assertSame(0.0, TextSimilarity::changeRatio('', ''));
    }
}
