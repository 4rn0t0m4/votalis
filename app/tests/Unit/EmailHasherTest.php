<?php

namespace Tests\Unit;

use App\Support\EmailHasher;
use Tests\TestCase;

class EmailHasherTest extends TestCase
{
    public function test_le_hache_est_stable_et_insensible_a_la_casse_et_aux_espaces(): void
    {
        $a = EmailHasher::hash('Citoyenne@Example.org');
        $b = EmailHasher::hash('  citoyenne@example.org ');

        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a));
        $this->assertNotSame($a, EmailHasher::hash('autre@example.org'));
    }

    public function test_le_hache_depend_de_la_cle_dediee_et_non_d_app_key(): void
    {
        $before = EmailHasher::hash('citoyenne@example.org');

        config(['votalis.email_hash_key' => 'base64:'.base64_encode(random_bytes(32))]);

        $this->assertNotSame($before, EmailHasher::hash('citoyenne@example.org'));
    }

    public function test_une_cle_manquante_declenche_une_erreur_explicite(): void
    {
        config(['votalis.email_hash_key' => '']);

        $this->expectException(\RuntimeException::class);
        EmailHasher::hash('citoyenne@example.org');
    }
}
