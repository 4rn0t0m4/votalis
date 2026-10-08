<?php

namespace Tests\Feature\Platform;

use App\Enums\ReportMotive;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicPageCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_pages_publiques_sont_servies_du_cache_aux_visiteurs_puis_invalidees_par_la_moderation(): void
    {
        Notification::fake();
        $proposal = Proposal::factory()->create(['title' => 'Fiche mise en cache']);

        $this->get($proposal->url())->assertOk()->assertHeader('X-Cache', 'MISS')->assertSee('Fiche mise en cache');
        $this->get($proposal->url())->assertOk()->assertHeader('X-Cache', 'HIT')->assertSee('Fiche mise en cache');

        // Un masquage invalide le cache : le visiteur voit le bandeau immédiatement.
        app(ModerationService::class)->hide(User::factory()->role(Role::Moderator)->withTwoFactor()->create(), $proposal, ReportMotive::Spam);
        $this->get($proposal->url())->assertOk()->assertHeader('X-Cache', 'MISS')->assertSee('Contenu masqué')->assertDontSee($proposal->measure);
    }

    public function test_les_utilisateurs_connectes_ne_sont_jamais_servis_depuis_le_cache(): void
    {
        $proposal = Proposal::factory()->create();
        $this->get($proposal->url())->assertHeader('X-Cache', 'MISS');
        $this->get($proposal->url())->assertHeader('X-Cache', 'HIT');

        $this->actingAs(User::factory()->create())->get($proposal->url())->assertOk()->assertHeaderMissing('X-Cache')->assertSee('Votre avis');
    }

    public function test_le_cache_se_desactive_par_configuration(): void
    {
        config(['votalis.cache.public_seconds' => 0]);
        $this->get('/')->assertOk()->assertHeaderMissing('X-Cache');
        $this->get('/')->assertOk()->assertHeaderMissing('X-Cache');
    }
}
