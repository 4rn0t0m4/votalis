<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'mot-de-passe-de-test-123';

    public function test_connexion_par_pseudonyme(): void
    {
        $user = User::factory()->create(['pseudonym' => 'Citoyenne_42']);

        $this->post('/connexion', ['identifier' => 'citoyenne_42', 'password' => self::PASSWORD])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_connexion_par_email_insensible_a_la_casse(): void
    {
        $user = User::factory()->create(['email' => 'citoyenne@example.org']);

        $this->post('/connexion', ['identifier' => 'Citoyenne@Example.org', 'password' => self::PASSWORD])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_mauvais_mot_de_passe_refuse(): void
    {
        User::factory()->create(['pseudonym' => 'Citoyenne_42']);

        $this->from('/connexion')
            ->post('/connexion', ['identifier' => 'Citoyenne_42', 'password' => 'mauvais-mot-de-passe'])
            ->assertRedirect('/connexion')
            ->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_la_session_est_regeneree_a_la_connexion(): void
    {
        User::factory()->create(['pseudonym' => 'Citoyenne_42']);

        $this->get('/connexion');
        $before = session()->getId();

        $this->post('/connexion', ['identifier' => 'Citoyenne_42', 'password' => self::PASSWORD]);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_verrouillage_progressif_apres_cinq_echecs(): void
    {
        User::factory()->create(['pseudonym' => 'Citoyenne_42']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/connexion', ['identifier' => 'Citoyenne_42', 'password' => 'mauvais'])->assertSessionHasErrors('identifier');
        }

        $response = $this->post('/connexion', ['identifier' => 'Citoyenne_42', 'password' => self::PASSWORD]);

        $response->assertSessionHasErrors('identifier');
        $this->assertStringContainsString('Trop de tentatives', session('errors')->first('identifier'));
        $this->assertGuest();
    }

    public function test_le_verrouillage_ne_touche_pas_les_autres_comptes(): void
    {
        User::factory()->create(['pseudonym' => 'Citoyenne_42']);
        $other = User::factory()->create(['pseudonym' => 'Autre_compte']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/connexion', ['identifier' => 'Citoyenne_42', 'password' => 'mauvais']);
        }

        $this->post('/connexion', ['identifier' => 'Autre_compte', 'password' => self::PASSWORD]);

        $this->assertAuthenticatedAs($other);
    }

    public function test_la_limitation_de_debit_brute_repond_429(): void
    {
        config(['votalis.lockout.failures' => 100]);

        for ($i = 0; $i < 10; $i++) {
            $this->post('/connexion', ['identifier' => 'inconnu', 'password' => 'mauvais']);
        }

        $this->post('/connexion', ['identifier' => 'inconnu', 'password' => 'mauvais'])->assertStatus(429);
    }

    public function test_deconnexion(): void
    {
        $this->actingAs(User::factory()->create())->post('/deconnexion')->assertRedirect('/');

        $this->assertGuest();
    }
}
