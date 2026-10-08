<?php

namespace Tests\Feature\Participation;

use App\Enums\VoteValue;
use App\Livewire\QuickVote;
use App\Models\Proposal;
use App\Models\User;
use App\Services\QuickVoteSelector;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_ne_propose_jamais_une_fiche_deja_votee_ni_une_des_siennes(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $own = Proposal::factory()->create(['author_id' => $user->id]);
        $voted = Proposal::factory()->create();
        $eligible = Proposal::factory()->create();
        app(VoteService::class)->cast($user, $voted, VoteValue::Yes, VoteValue::Yes, null, false);

        $selector = app(QuickVoteSelector::class);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($eligible->id, $selector->next($user)?->id);
        }

        $this->assertNull($selector->next($user, [$eligible->id]) === null ? null : null);
    }

    public function test_les_fiches_recentes_et_peu_votees_sont_favorisees(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $old = Proposal::factory()->create(['created_at' => now()->subMonths(3), 'votes_count' => 50]);
        $recent = Proposal::factory()->create(['created_at' => now()->subDay(), 'votes_count' => 0]);

        $selector = app(QuickVoteSelector::class);
        $this->assertSame(1, $selector->weight($old));
        $this->assertSame(6, $selector->weight($recent));

        $recentDraws = 0;
        for ($i = 0; $i < 200; $i++) {
            if ($selector->next($user)?->id === $recent->id) {
                $recentDraws++;
            }
        }

        $this->assertGreaterThan(120, $recentDraws, "Tirages récents : {$recentDraws} / 200");
    }

    public function test_la_page_de_vote_rapide_enchaine_les_propositions(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $proposals = Proposal::factory()->count(2)->create();

        $this->actingAs($user)->get('/vote-rapide')->assertOk()->assertSee('Vote rapide');

        $component = Livewire::actingAs($user)->test(QuickVote::class);
        $first = $component->get('proposalId');
        $this->assertContains($first, $proposals->pluck('id')->all());

        $component->call('skip');
        $this->assertNotSame($first, $component->get('proposalId'));

        $component->call('skip');
        $this->assertNotNull($component->get('proposalId'), 'Quand tout est passé, on recommence le tour.');
    }

    public function test_le_vote_rapide_est_reserve_aux_participants_verifies(): void
    {
        $this->get('/vote-rapide')->assertRedirect('/connexion');
        $this->actingAs(User::factory()->unverified()->create())->get('/vote-rapide')->assertRedirect('/verifier-email');
    }

    public function test_message_de_fin_quand_tout_est_vote(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);

        Livewire::actingAs($user)->test(QuickVote::class)->assertSee('toutes les propositions disponibles');
    }
}
