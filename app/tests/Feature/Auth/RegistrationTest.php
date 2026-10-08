<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use App\Support\EmailHasher;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_d_inscription_s_affiche(): void
    {
        $this->get('/inscription')->assertOk()->assertSee('Créer un compte');
    }

    public function test_un_compte_est_cree_avec_email_chiffre_et_hache(): void
    {
        Notification::fake();

        $this->post('/inscription', $this->validRegistration())->assertRedirect('/');

        $this->assertAuthenticated();
        $user = User::sole();

        $this->assertSame('Citoyenne_42', $user->pseudonym);
        $this->assertSame('citoyenne@example.org', $user->email);
        $this->assertSame(Role::Participant, $user->role);
        $this->assertNotNull($user->consented_at);
        $this->assertNull($user->email_verified_at);

        $raw = DB::table('users')->first();
        $this->assertNotSame('citoyenne@example.org', $raw->email, 'L\'e-mail doit être chiffré en base.');
        $this->assertStringNotContainsString('example.org', $raw->email);
        $this->assertSame(EmailHasher::hash('citoyenne@example.org'), $raw->email_hash);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_l_email_est_normalise_avant_stockage(): void
    {
        $this->post('/inscription', $this->validRegistration(['email' => '  Citoyenne@Example.ORG ']));

        $this->assertSame('citoyenne@example.org', User::sole()->email);
    }

    public function test_un_domaine_jetable_est_refuse(): void
    {
        $this->from('/inscription')
            ->post('/inscription', $this->validRegistration(['email' => 'test@mailinator.com']))
            ->assertRedirect('/inscription')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_un_mot_de_passe_trop_court_est_refuse(): void
    {
        $this->post('/inscription', $this->validRegistration(['password' => 'court123', 'password_confirmation' => 'court123']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_un_mot_de_passe_present_dans_une_fuite_est_refuse(): void
    {
        $this->fakeLeakedPassword('une-phrase-longue-et-sure');

        $this->post('/inscription', $this->validRegistration())->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_le_consentement_est_obligatoire(): void
    {
        $data = $this->validRegistration();
        unset($data['consent']);

        $this->post('/inscription', $data)->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_un_email_deja_utilise_est_refuse_quelle_que_soit_la_casse(): void
    {
        User::factory()->create(['email' => 'citoyenne@example.org']);

        $this->post('/inscription', $this->validRegistration(['email' => 'CITOYENNE@example.org', 'pseudonym' => 'Autre_pseudo']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_un_pseudonyme_deja_utilise_est_refuse_quelle_que_soit_la_casse(): void
    {
        User::factory()->create(['pseudonym' => 'citoyenne_42']);

        $this->post('/inscription', $this->validRegistration(['email' => 'autre@example.org']))
            ->assertSessionHasErrors('pseudonym');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_un_pseudonyme_avec_caracteres_interdits_est_refuse(): void
    {
        $this->post('/inscription', $this->validRegistration(['pseudonym' => 'pseudo avec espaces']))
            ->assertSessionHasErrors('pseudonym');

        $this->post('/inscription', $this->validRegistration(['pseudonym' => 'ab']))
            ->assertSessionHasErrors('pseudonym');
    }

    public function test_l_inscription_est_limitee_en_debit_par_adresse_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/inscription', $this->validRegistration(['pseudonym' => 'ab']))->assertSessionHasErrors('pseudonym');
        }

        $this->post('/inscription', $this->validRegistration())->assertStatus(429);
        $this->assertDatabaseCount('users', 0);
    }
}
