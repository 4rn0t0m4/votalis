<?php

namespace Tests\Feature\Account;

use App\Enums\VoteValue;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\Tradeoff;
use App\Models\User;
use App\Services\TradeoffService;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Page « Mon parcours », parcours de démarrage sur l'accueil, célébration unique. */
class JourneyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_est_reservee_au_participant_connecte(): void
    {
        $this->get('/mon-compte/parcours')->assertRedirect('/connexion');
    }

    public function test_la_page_affiche_chiffres_et_jalons_avec_leur_regle(): void
    {
        Theme::factory()->count(3)->create();
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::Yes, VoteValue::No, null, true);

        $this->actingAs($user)->get('/mon-compte/parcours')->assertOk()
            ->assertSee('Mon parcours')
            ->assertSee('Visible par vous seul')
            ->assertSee('2 sur 9')
            ->assertSee('Première voix')
            ->assertSee('Voter après avoir déplié les arguments')
            ->assertSee('Jalon atteint')
            ->assertSeeInOrder(['Esprit ouvert', 'Réviser un vote après lecture des arguments']);

        // La célébration ne s'affiche qu'une fois.
        $this->actingAs($user)->get('/mon-compte/parcours')->assertOk()->assertDontSee('Jalon atteint');
    }

    public function test_l_accueil_propose_trois_pas_puis_les_retire(): void
    {
        $user = User::factory()->create(['pseudonym' => 'Nouvelle_Venue', 'created_at' => now()->subMonth()]);

        $this->get('/')->assertOk()->assertDontSee('Trois pas pour commencer');

        $this->actingAs($user)->get('/')->assertOk()
            ->assertSee('Bienvenue, Nouvelle_Venue')
            ->assertSee('0 sur 3')
            ->assertSee('Vote rapide, 2 minutes');

        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::Yes, VoteValue::No, null, true);
        $tradeoff = Tradeoff::factory()->create(['constraint_value' => 10]);
        $item = app(TradeoffService::class)->addItem($tradeoff, ['proposal_id' => Proposal::factory()->create()->id, 'impact' => 12, 'uncertainty' => '± 20 %', 'source_url' => 'https://www.exemple.gouv.fr/chiffrage']);
        app(TradeoffService::class)->answer($user, $tradeoff, [$item->id]);

        $this->actingAs($user)->get('/')->assertOk()->assertDontSee('Trois pas pour commencer');
    }

    public function test_une_celebration_apparait_sur_la_page_suivante_puis_disparait(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::Yes, VoteValue::No, null, false);

        $this->actingAs($user)->get('/themes')->assertOk()->assertSee('Jalon atteint')->assertSee('Première voix');
        $this->actingAs($user)->get('/themes')->assertOk()->assertDontSee('Jalon atteint');
    }

    public function test_mon_compte_renvoie_vers_le_parcours(): void
    {
        $this->actingAs(User::factory()->create())->get('/mon-compte')->assertOk()->assertSee('/mon-compte/parcours');
    }
}
