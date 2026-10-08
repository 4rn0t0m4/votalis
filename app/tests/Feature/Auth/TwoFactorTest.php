<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'mot-de-passe-de-test-123';

    private function currentCode(User $user): string
    {
        return (new Google2FA)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));
    }

    public function test_l_activation_exige_la_confirmation_du_mot_de_passe(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/user/two-factor-authentication')->assertRedirect('/mon-compte/confirmer-mot-de-passe');

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_activation_puis_confirmation_par_code_totp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-authentication')
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/confirmed-two-factor-authentication', ['code' => $this->currentCode($user)])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertCount(8, $user->fresh()->recoveryCodes());
    }

    public function test_un_code_errone_ne_confirme_pas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->post('/user/two-factor-authentication');

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/confirmed-two-factor-authentication', ['code' => '000000'])
            ->assertSessionHasErrors();

        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_la_connexion_avec_double_facteur_passe_par_le_defi(): void
    {
        $user = User::factory()->withTwoFactor()->create(['pseudonym' => 'Citoyenne_42']);

        $this->post('/connexion', ['identifier' => 'Citoyenne_42', 'password' => self::PASSWORD])
            ->assertRedirect('/double-authentification');

        $this->assertGuest();

        $this->post('/double-authentification', ['code' => $this->currentCode($user)])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_un_code_de_secours_fonctionne_une_seule_fois(): void
    {
        $user = User::factory()->withTwoFactor()->create(['pseudonym' => 'Citoyenne_42']);

        $this->post('/connexion', ['identifier' => 'Citoyenne_42', 'password' => self::PASSWORD]);
        $this->post('/double-authentification', ['recovery_code' => 'code-1'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->assertNotContains('code-1', $user->fresh()->recoveryCodes());
    }

    public function test_un_moderateur_sans_second_facteur_est_bloque_sur_la_page_securite(): void
    {
        $moderator = User::factory()->role(Role::Moderator)->create();

        $this->actingAs($moderator)->get('/mon-compte')->assertRedirect('/mon-compte/securite');
        $this->actingAs($moderator)->get('/')->assertRedirect('/mon-compte/securite');
        $this->actingAs($moderator)->get('/mon-compte/securite')->assertOk()->assertSee('exige une double authentification');
    }

    public function test_le_comite_editorial_et_l_admin_sont_aussi_bloques(): void
    {
        foreach ([Role::Editorial, Role::Admin] as $role) {
            $this->actingAs(User::factory()->role($role)->create())->get('/mon-compte')->assertRedirect('/mon-compte/securite');
        }
    }

    public function test_un_moderateur_avec_totp_confirme_a_acces(): void
    {
        $moderator = User::factory()->role(Role::Moderator)->withTwoFactor()->create();

        $this->actingAs($moderator)->get('/mon-compte')->assertOk();
    }

    public function test_un_participant_n_est_jamais_contraint(): void
    {
        $this->actingAs(User::factory()->create())->get('/mon-compte')->assertOk();
    }

    public function test_le_second_facteur_ne_peut_pas_etre_desactive_sans_confirmation_du_mot_de_passe(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $this->actingAs($user)->delete('/user/two-factor-authentication')->assertRedirect('/mon-compte/confirmer-mot-de-passe');

        $this->assertNotNull($user->fresh()->two_factor_secret);
    }
}
