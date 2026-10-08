<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_compte_non_verifie_est_renvoye_vers_la_page_de_verification(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/mon-compte')
            ->assertRedirect('/verifier-email');
    }

    public function test_le_lien_signe_verifie_l_email(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($url)->assertRedirect('/?verified=1');

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_un_lien_expire_ou_altere_est_refuse(): void
    {
        $user = User::factory()->unverified()->create();

        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($expired)->assertForbidden();

        $wrongHash = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1('autre@example.org')]);
        $this->actingAs($user)->get($wrongHash)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_le_lien_peut_etre_renvoye(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/verifier-email/renvoyer')->assertSessionHas('status', 'verification-link-sent');
    }
}
