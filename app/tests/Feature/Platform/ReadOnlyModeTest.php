<?php

namespace Tests\Feature\Platform;

use App\Enums\Role;
use App\Enums\VoteValue;
use App\Livewire\VoteBox;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ReadOnlyMode;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ReadOnlyModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_en_lecture_seule_toute_ecriture_est_refusee_et_la_lecture_reste_possible(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $proposal = Proposal::factory()->create();
        app(ReadOnlyMode::class)->enable('Maintenance en cours, merci de patienter.');

        // Lecture et connexion : oui.
        $this->get('/')->assertOk()->assertSee('Maintenance en cours');
        $this->get($proposal->url())->assertOk();
        $this->actingAs($user)->get('/mon-compte')->assertOk();
        $this->post('/connexion', ['identifier' => 'inconnu@example.org', 'password' => 'x'])->assertRedirect();

        // Écritures HTTP : 503 avec message.
        $this->actingAs($user)->post("/signaler/proposition/{$proposal->id}", ['motive' => 'spam'])->assertStatus(503)->assertSee('Maintenance en cours');
        $this->assertDatabaseCount('reports', 0);

        // Écritures Livewire : refusées par le service même sans passer par le middleware.
        try {
            app(VoteService::class)->cast($user, $proposal, VoteValue::Yes, VoteValue::Yes, null, false);
            $this->fail('Le vote doit être refusé en lecture seule.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('read_only', $e->errors());
        }
        Livewire::actingAs($user)->test(VoteBox::class, ['proposal' => $proposal])
            ->set('desirable', 1)->set('necessary', 1)->call('vote')
            ->assertHasErrors('read_only');
        $this->assertDatabaseCount('votes', 0);

        app(ReadOnlyMode::class)->disable();
        $this->get('/')->assertOk()->assertDontSee('Maintenance en cours');
        app(VoteService::class)->cast($user, $proposal, VoteValue::Yes, VoteValue::Yes, null, false);
        $this->assertDatabaseCount('votes', 1);
    }

    public function test_seul_l_administrateur_technique_bascule_le_mode_depuis_la_page_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Editorial)->withTwoFactor()->create())->get('/admin')->assertForbidden();

        $admin = User::factory()->role(Role::Admin)->withTwoFactor()->create();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Passer en lecture seule');
        $this->actingAs($admin)->post('/admin/lecture-seule', ['enabled' => '1', 'message' => 'Attaque en cours, lecture seule.'])->assertRedirect('/admin');
        $this->assertTrue(app(ReadOnlyMode::class)->enabled());
        $this->assertSame('Attaque en cours, lecture seule.', app(ReadOnlyMode::class)->message());

        // L'administrateur peut encore rétablir (route autorisée en lecture seule).
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Rétablir les écritures');
        $this->actingAs($admin)->post('/admin/lecture-seule', ['enabled' => '0'])->assertRedirect('/admin');
        $this->assertFalse(app(ReadOnlyMode::class)->enabled());
    }

    public function test_la_commande_bascule_le_mode(): void
    {
        $this->artisan('votalis:read-only', ['state' => 'on', '--message' => 'Pause technique.'])->assertSuccessful();
        $this->assertTrue(app(ReadOnlyMode::class)->enabled());
        $this->assertSame('Pause technique.', app(ReadOnlyMode::class)->message());
        $this->artisan('votalis:read-only', ['state' => 'off'])->assertSuccessful();
        $this->assertFalse(app(ReadOnlyMode::class)->enabled());
        $this->assertSame(ReadOnlyMode::DEFAULT_MESSAGE, app(ReadOnlyMode::class)->message());
    }
}
