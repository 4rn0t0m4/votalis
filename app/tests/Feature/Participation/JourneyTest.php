<?php

namespace Tests\Feature\Participation;

use App\Enums\ArgumentSide;
use App\Enums\Milestone;
use App\Enums\VoteValue;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\Tradeoff;
use App\Models\User;
use App\Services\AccountEraser;
use App\Services\AccountExporter;
use App\Services\Journey;
use App\Services\ProposalService;
use App\Services\TradeoffService;
use App\Services\VoteService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Parcours personnel (lot 6, F4 et F6) : chaque jalon se déclenche exactement sur sa règle,
 * jamais sur un volume ; le parcours est exporté et supprimé avec le compte.
 */
class JourneyTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create(['created_at' => now()->subMonth()]);
    }

    /** @return list<string> */
    private function keys(User $user): array
    {
        return array_keys(app(Journey::class)->reached($user));
    }

    public function test_aucun_jalon_sans_action_et_premiere_voix_au_premier_vote(): void
    {
        $user = $this->participant();
        $this->assertSame([], app(Journey::class)->evaluate($user));
        $this->assertSame([], $this->keys($user));

        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::Yes, VoteValue::No, null, false);

        $this->assertSame([Milestone::FirstVoice->value], $this->keys($user));
    }

    public function test_un_volume_de_votes_sans_lecture_ne_debloque_que_la_premiere_voix(): void
    {
        $user = $this->participant();
        $theme = Theme::factory()->create();
        config(['votalis.caps.votes_per_day' => 500]);

        foreach (Proposal::factory()->count(40)->create(['theme_id' => $theme->id]) as $proposal) {
            app(VoteService::class)->cast($user, $proposal, VoteValue::Yes, VoteValue::Yes, null, false);
        }

        $this->assertSame([Milestone::FirstVoice->value], $this->keys($user));
        $this->assertSame(40, app(Journey::class)->stats($user)['votes']);
    }

    public function test_lecture_complete_exige_un_vote_avec_les_arguments_visibles(): void
    {
        $user = $this->participant();
        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::Yes, VoteValue::Yes, null, false);
        $this->assertNotContains(Milestone::FullReading->value, $this->keys($user));

        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::No, VoteValue::No, null, true);
        $this->assertContains(Milestone::FullReading->value, $this->keys($user));
    }

    public function test_esprit_ouvert_exige_une_revision_apres_lecture_quel_que_soit_le_sens(): void
    {
        $user = $this->participant();
        $proposal = Proposal::factory()->create();
        app(VoteService::class)->cast($user, $proposal, VoteValue::Yes, VoteValue::Yes, null, true);
        $this->assertNotContains(Milestone::OpenMind->value, $this->keys($user));

        // Révision sans changement : rien.
        app(VoteService::class)->cast($user, $proposal, VoteValue::Yes, VoteValue::Yes, null, true);
        $this->assertNotContains(Milestone::OpenMind->value, $this->keys($user));

        // Passage de oui à non (le sens n'importe pas).
        app(VoteService::class)->cast($user, $proposal, VoteValue::No, VoteValue::Yes, null, true);
        $this->assertContains(Milestone::OpenMind->value, $this->keys($user));
    }

    public function test_nuance_exige_une_condition(): void
    {
        $user = $this->participant();
        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::Yes, VoteValue::Yes, 'elle soit évaluée', false);

        $this->assertContains(Milestone::Nuance->value, $this->keys($user));
    }

    public function test_deux_points_de_vue_exige_un_utile_de_chaque_cote(): void
    {
        $user = $this->participant();
        $proposal = Proposal::factory()->create();
        $for = Argument::factory()->create(['proposal_id' => $proposal->id, 'side' => ArgumentSide::For]);
        $against = Argument::factory()->create(['proposal_id' => $proposal->id, 'side' => ArgumentSide::Against]);

        $user->markedArguments()->attach($for->id);
        app(Journey::class)->evaluate($user);
        $this->assertNotContains(Milestone::TwoViewpoints->value, $this->keys($user));

        $user->markedArguments()->attach($against->id);
        app(Journey::class)->evaluate($user);
        $this->assertContains(Milestone::TwoViewpoints->value, $this->keys($user));
    }

    public function test_source_et_proposant(): void
    {
        $user = $this->participant();
        $proposal = Proposal::factory()->create();
        Argument::factory()->create(['proposal_id' => $proposal->id, 'author_id' => $user->id, 'source_url' => null]);
        app(Journey::class)->evaluate($user);
        $this->assertNotContains(Milestone::Sourced->value, $this->keys($user));

        Argument::factory()->create(['proposal_id' => $proposal->id, 'author_id' => $user->id, 'source_url' => 'https://www.exemple.gouv.fr/rapport']);
        app(Journey::class)->evaluate($user);
        $this->assertContains(Milestone::Sourced->value, $this->keys($user));

        app(ProposalService::class)->create([
            'theme_id' => Theme::factory()->create()->id, 'title' => 'Créer un service public de proximité',
            'problem' => 'Un problème décrit avec assez de détails pour être recevable.', 'measure' => 'Une mesure décrite avec assez de détails pour être recevable par le service.',
            'cost_estimate' => 'Inconnu', 'cost_unknown' => true, 'sources' => [], 'personal_source' => true,
        ], $user);
        $this->assertContains(Milestone::Proposer->value, $this->keys($user));
    }

    public function test_arbitre_a_la_validation_d_une_combinaison(): void
    {
        $user = $this->participant();
        $tradeoff = Tradeoff::factory()->create(['constraint_value' => 10]);
        $item = app(TradeoffService::class)->addItem($tradeoff, ['proposal_id' => Proposal::factory()->create()->id, 'impact' => 12, 'uncertainty' => '± 20 %', 'source_url' => 'https://www.exemple.gouv.fr/chiffrage']);

        app(TradeoffService::class)->answer($user, $tradeoff, [$item->id]);

        $this->assertContains(Milestone::Arbiter->value, $this->keys($user));
    }

    public function test_exploration_compte_les_themes_de_premier_niveau_distincts(): void
    {
        $user = $this->participant();
        config(['votalis.journey.exploration_themes' => 3]);
        $root = Theme::factory()->create();
        $child = Theme::factory()->childOf($root)->create();

        app(VoteService::class)->cast($user, Proposal::factory()->create(['theme_id' => $root->id]), VoteValue::Yes, VoteValue::Yes, null, false);
        app(VoteService::class)->cast($user, Proposal::factory()->create(['theme_id' => $child->id]), VoteValue::Yes, VoteValue::Yes, null, false);
        app(VoteService::class)->cast($user, Proposal::factory()->create(['theme_id' => Theme::factory()->create()->id]), VoteValue::Yes, VoteValue::Yes, null, false);
        $this->assertSame(2, app(Journey::class)->stats($user)['themes']);
        $this->assertNotContains(Milestone::Exploration->value, $this->keys($user));

        app(VoteService::class)->cast($user, Proposal::factory()->create(['theme_id' => Theme::factory()->create()->id]), VoteValue::Yes, VoteValue::Yes, null, false);
        $this->assertContains(Milestone::Exploration->value, $this->keys($user));
    }

    public function test_l_evaluation_est_idempotente_et_la_celebration_unique(): void
    {
        $user = $this->participant();
        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::Yes, VoteValue::Yes, null, false);
        $journey = app(Journey::class);

        $this->assertSame([], $journey->evaluate($user));
        $this->assertSame(1, DB::table('milestones')->where('participant_id', $user->id)->count());

        $this->assertSame([Milestone::FirstVoice], $journey->takeFresh($user));
        $this->assertSame([], $journey->takeFresh($user));
    }

    public function test_le_parcours_est_exporte_et_supprime_avec_le_compte(): void
    {
        $user = $this->participant();
        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::Yes, VoteValue::Yes, 'elle soit évaluée', true);

        $export = app(AccountExporter::class)->export($user);
        $this->assertSame(['premiere_voix', 'lecture_complete', 'oui_a_condition'], array_column($export['parcours'], 'jalon'));
        $this->assertTrue($export['votes'][0]['apres_arguments']);

        app(AccountEraser::class)->erase($user);
        $this->assertSame(0, DB::table('milestones')->where('participant_id', $user->id)->count());
    }

    public function test_la_base_refuse_un_jalon_inconnu(): void
    {
        $user = $this->participant();
        $this->expectException(QueryException::class);

        DB::beginTransaction();
        try {
            DB::table('milestones')->insert(['participant_id' => $user->id, 'key' => 'champion', 'reached_at' => now()]);
        } finally {
            DB::rollBack();
        }
    }
}
