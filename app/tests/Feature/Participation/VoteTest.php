<?php

namespace Tests\Feature\Participation;

use App\Enums\VoteValue;
use App\Livewire\VoteBox;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Vote;
use App\Services\ProposalService;
use App\Services\VoteService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    private function voter(): User
    {
        return User::factory()->create(['created_at' => now()->subMonth()]);
    }

    public function test_un_vote_par_compte_et_par_proposition_revisable_avec_vote_initial_conserve(): void
    {
        $user = $this->voter();
        $proposal = Proposal::factory()->create();
        $service = app(VoteService::class);

        $service->cast($user, $proposal, VoteValue::No, VoteValue::Unsure, null, false);
        $service->cast($user, $proposal, VoteValue::Yes, VoteValue::Yes, 'elle soit évaluée après trois ans', true);

        $this->assertDatabaseCount('votes', 1);
        $vote = Vote::query()->where('participant_id', $user->id)->sole();
        $this->assertSame(VoteValue::Yes, $vote->desirable);
        $this->assertSame(VoteValue::No, $vote->desirable_initial);
        $this->assertSame(VoteValue::Unsure, $vote->necessary_initial);
        $this->assertTrue($vote->revised_after_arguments);
        $this->assertSame('elle soit évaluée après trois ans', $vote->condition);
        $this->assertSame(1, $proposal->fresh()->votes_count);
    }

    public function test_une_revision_sans_arguments_visibles_n_est_pas_comptee_comme_apres_lecture(): void
    {
        $user = $this->voter();
        $proposal = Proposal::factory()->create();
        $service = app(VoteService::class);

        $service->cast($user, $proposal, VoteValue::No, VoteValue::No, null, false);
        $service->cast($user, $proposal, VoteValue::Yes, VoteValue::No, null, false);

        $this->assertFalse(Vote::sole()->revised_after_arguments);
    }

    public function test_le_premier_vote_verrouille_le_fond_de_la_fiche(): void
    {
        $proposal = Proposal::factory()->create();
        $this->assertFalse($proposal->isLocked());

        app(VoteService::class)->cast($this->voter(), $proposal, VoteValue::Yes, VoteValue::Yes, null, false);

        $this->assertTrue($proposal->fresh()->isLocked());

        $this->expectException(ValidationException::class);
        app(ProposalService::class)->update($proposal->fresh(), [
            'theme_id' => $proposal->theme_id, 'title' => $proposal->title, 'problem' => $proposal->problem,
            'measure' => 'Un dispositif entièrement différent qui change le sens de la mesure.',
            'cost_estimate' => $proposal->cost_estimate, 'cost_unknown' => false,
            'sources' => ['https://www.exemple.gouv.fr/rapport'], 'personal_source' => false,
        ], $proposal->author);
    }

    public function test_on_ne_vote_pas_sur_sa_propre_proposition(): void
    {
        $proposal = Proposal::factory()->create();

        try {
            app(VoteService::class)->cast($proposal->author, $proposal, VoteValue::Yes, VoteValue::Yes, null, false);
            $this->fail('Le vote de l’auteur aurait dû être refusé.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('proposal', $e->errors());
        }

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_le_plafond_de_votes_par_jour_est_refuse_cote_serveur(): void
    {
        config(['votalis.caps.votes_per_day' => 2]);
        $user = $this->voter();
        $proposals = Proposal::factory()->count(3)->create();
        $service = app(VoteService::class);

        $service->cast($user, $proposals[0], VoteValue::Yes, VoteValue::Yes, null, false);
        $service->cast($user, $proposals[1], VoteValue::Yes, VoteValue::Yes, null, false);

        try {
            $service->cast($user, $proposals[2], VoteValue::Yes, VoteValue::Yes, null, false);
            $this->fail('Le plafond aurait dû bloquer.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cap', $e->errors());
        }

        // Réviser un vote existant reste possible.
        $service->cast($user, $proposals[0], VoteValue::No, VoteValue::No, null, false);
        $this->assertDatabaseCount('votes', 2);

        // Et par l'action Livewire, pas seulement par le service.
        Livewire::actingAs($user)->test(VoteBox::class, ['proposal' => $proposals[2]])
            ->set('desirable', 1)->set('necessary', 1)->call('vote')->assertHasErrors(['cap']);
        $this->assertDatabaseCount('votes', 2);
    }

    public function test_une_condition_exige_un_oui_et_200_caracteres_maximum(): void
    {
        $user = $this->voter();
        $proposal = Proposal::factory()->create();
        $service = app(VoteService::class);

        try {
            $service->cast($user, $proposal, VoteValue::No, VoteValue::No, 'si on la finance', false);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('condition', $e->errors());
        }

        try {
            $service->cast($user, $proposal, VoteValue::Yes, VoteValue::No, str_repeat('a', 201), false);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('condition', $e->errors());
        }

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_les_resultats_detailles_n_apparaissent_qu_apres_le_vote(): void
    {
        $proposal = Proposal::factory()->create();
        $others = User::factory()->count(3)->create(['created_at' => now()->subMonth()]);
        foreach ($others as $other) {
            app(VoteService::class)->cast($other, $proposal, VoteValue::Yes, VoteValue::No, null, false);
        }

        $this->get($proposal->url())->assertOk()->assertSeeText('Connectez-vous pour voter')->assertDontSee('Résultats détaillés');

        $user = $this->voter();
        $this->actingAs($user)->get($proposal->url())->assertOk()->assertSee('Souhaitable pour vous ?')->assertDontSee('Résultats détaillés');

        Livewire::actingAs($user)->test(VoteBox::class, ['proposal' => $proposal])
            ->set('desirable', -1)->set('necessary', 0)->call('vote')->assertHasNoErrors()
            ->assertSee('Résultats détaillés')->assertSee('4 votes')->assertSee('75 %');
    }

    public function test_la_base_refuse_une_valeur_de_vote_inconnue(): void
    {
        $this->expectException(QueryException::class);
        DB::table('votes')->insert([
            'participant_id' => User::factory()->create()->id, 'proposal_id' => Proposal::factory()->create()->id,
            'desirable' => 5, 'necessary' => 1, 'desirable_initial' => 5, 'necessary_initial' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_les_votes_sont_effaces_avec_le_compte(): void
    {
        $user = $this->voter();
        $proposal = Proposal::factory()->create();
        app(VoteService::class)->cast($user, $proposal, VoteValue::Yes, VoteValue::Yes, null, false);

        $user->delete();

        $this->assertDatabaseCount('votes', 0);
        $this->assertDatabaseHas('proposals', ['id' => $proposal->id]);
    }
}
