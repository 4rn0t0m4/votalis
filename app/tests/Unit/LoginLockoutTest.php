<?php

namespace Tests\Unit;

use App\Auth\LoginLockout;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
{
    public function test_les_durees_de_verrouillage_progressent_puis_plafonnent(): void
    {
        $lockout = new LoginLockout(Cache::store('array'));
        $key = LoginLockout::key('Citoyenne_42', '203.0.113.1');

        $this->assertSame(0, $lockout->remainingSeconds($key));

        foreach ([60, 300, 900, 900] as $expected) {
            for ($i = 0; $i < 5; $i++) {
                $lockout->recordFailure($key);
            }

            $this->assertEqualsWithDelta($expected, $lockout->remainingSeconds($key), 2);
            Cache::store('array')->forget("lockout:until:{$key}");
        }
    }

    public function test_une_connexion_reussie_remet_le_compteur_a_zero(): void
    {
        $lockout = new LoginLockout(Cache::store('array'));
        $key = LoginLockout::key('Citoyenne_42', '203.0.113.1');

        for ($i = 0; $i < 5; $i++) {
            $lockout->recordFailure($key);
        }
        $this->assertGreaterThan(0, $lockout->remainingSeconds($key));

        $lockout->clear($key);

        $this->assertSame(0, $lockout->remainingSeconds($key));
    }

    public function test_la_cle_ne_contient_ni_identifiant_ni_ip_en_clair(): void
    {
        $key = LoginLockout::key('citoyenne@example.org', '203.0.113.1');

        $this->assertStringNotContainsString('example.org', $key);
        $this->assertStringNotContainsString('203.0.113.1', $key);
        $this->assertSame($key, LoginLockout::key('  Citoyenne@Example.org ', '203.0.113.1'));
    }
}
