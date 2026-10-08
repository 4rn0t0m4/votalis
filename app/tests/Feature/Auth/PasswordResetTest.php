<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_table_des_jetons_ne_contient_pas_l_email_en_clair(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'citoyenne@example.org']);

        $this->post('/mot-de-passe-oublie', ['email' => 'Citoyenne@example.org'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);

        $row = DB::table('password_reset_tokens')->sole();
        $this->assertSame($user->email_hash, $row->email);
        $this->assertStringNotContainsString('@', $row->email);
    }

    public function test_le_mot_de_passe_est_reinitialise_avec_un_jeton_valide(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'citoyenne@example.org']);

        $this->post('/mot-de-passe-oublie', ['email' => 'citoyenne@example.org']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->post('/reinitialiser-mot-de-passe', [
                'token' => $notification->token,
                'email' => 'citoyenne@example.org',
                'password' => 'nouveau-mot-de-passe-solide',
                'password_confirmation' => 'nouveau-mot-de-passe-solide',
            ])->assertSessionHasNoErrors();

            $this->assertTrue(Hash::check('nouveau-mot-de-passe-solide', $user->fresh()->password));

            return true;
        });
    }

    public function test_un_email_inconnu_ne_revele_rien(): void
    {
        $this->post('/mot-de-passe-oublie', ['email' => 'inconnu@example.org'])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }
}
