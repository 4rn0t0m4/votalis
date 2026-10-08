<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_changer_d_email_invalide_la_verification_et_renvoie_un_lien(): void
    {
        Notification::fake();
        $user = User::factory()->create(['pseudonym' => 'Citoyenne_42', 'email' => 'avant@example.org']);

        $this->actingAs($user)
            ->put('/user/profile-information', ['pseudonym' => 'Citoyenne_42', 'email' => 'apres@example.org'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('apres@example.org', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_changer_de_pseudonyme_seul_garde_la_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['pseudonym' => 'Avant', 'email' => 'moi@example.org']);

        $this->actingAs($user)
            ->put('/user/profile-information', ['pseudonym' => 'Apres', 'email' => 'MOI@example.org'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Apres', $user->fresh()->pseudonym);
        $this->assertNotNull($user->fresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_le_pseudonyme_d_un_autre_compte_est_refuse(): void
    {
        User::factory()->create(['pseudonym' => 'Occupe']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/user/profile-information', ['pseudonym' => 'occupe', 'email' => $user->email])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'pseudonym');
    }

    public function test_la_serialisation_du_compte_n_expose_jamais_l_email(): void
    {
        $user = User::factory()->create(['email' => 'moi@example.org']);

        $json = $user->toJson();

        $this->assertStringNotContainsString('example.org', $json);
        $this->assertStringNotContainsString('email_hash', $json);
        $this->assertStringNotContainsString('two_factor', $json);
    }
}
