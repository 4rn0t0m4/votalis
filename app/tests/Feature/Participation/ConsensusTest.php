<?php

namespace Tests\Feature\Participation;

use App\Enums\ConsensusRunStatus;
use App\Models\ConsensusRun;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use App\Services\Consensus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Classement par consensus (lot 7) : codé mais désactivé par défaut ; seuils, pseudonymisation de
 * la charge, enregistrement et affichage des scores, résistance aux pannes, audit.
 * Le calcul lui-même est testé dans le service Python (consensus/tests).
 */
class ConsensusTest extends TestCase
{
    use RefreshDatabase;

    private Theme $theme;

    /** @var list<Proposal> */
    private array $proposals = [];

    /** @var list<User> */
    private array $voters = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['votalis.embeddings.url' => 'http://embeddings:8000', 'votalis.rankings.cache_seconds' => 0]);
    }

    private function enable(): void
    {
        config([
            'votalis.consensus.enabled' => true,
            'votalis.consensus.min_participants' => 3,
            'votalis.consensus.min_proposals' => 2,
            'votalis.consensus.min_votes_per_participant' => 2,
        ]);
    }

    /** Trois fiches, quatre votants actifs et un votant occasionnel (un seul vote, écarté). */
    private function population(): void
    {
        $this->theme = Theme::factory()->create();
        foreach (['Fiche consensuelle', 'Fiche clivante', 'Fiche tiède'] as $title) {
            $this->proposals[] = Proposal::factory()->create(['theme_id' => $this->theme->id, 'title' => $title]);
        }

        foreach (range(1, 4) as $i) {
            $user = User::factory()->create(['pseudonym' => "votant-{$i}", 'email' => "votant{$i}@example.test"]);
            $this->voters[] = $user;
            foreach ($this->proposals as $proposal) {
                $this->vote($user, $proposal, $i % 2 === 0 ? 1 : -1);
            }
        }

        $occasional = User::factory()->create(['pseudonym' => 'occasionnel']);
        $this->vote($occasional, $this->proposals[0], 1);
    }

    private function vote(User $user, Proposal $proposal, int $value): void
    {
        DB::table('votes')->insert([
            'participant_id' => $user->id, 'proposal_id' => $proposal->id,
            'desirable' => $value, 'necessary' => $value, 'desirable_initial' => $value, 'necessary_initial' => $value,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Réponse factice du service : scores fournis par identifiant de proposition.
     *
     * @param  array<int, array{0: float|null, 1: float|null}>  $scores
     */
    private function fakeService(array $scores): void
    {
        Http::fake(['embeddings:8000/consensus' => function (Request $request) use ($scores) {
            /** @var list<int> $ids */
            $ids = $request->data()['proposals'];

            return Http::response([
                'algo_version' => 'consensus-min-1',
                'status' => 'computed',
                'participants' => 4,
                'k' => 2,
                'silhouette' => 0.81,
                'groups' => [['label' => 'A', 'size' => 2], ['label' => 'B', 'size' => 2]],
                'proposals' => array_map(fn (int $id) => [
                    'proposal' => $id,
                    'score' => $scores[$id][0] ?? null,
                    'divisiveness' => $scores[$id][1] ?? null,
                    'groups' => [
                        ['label' => 'A', 'voters' => 2, 'agree_rate' => 0.82, 'necessary_rate' => 0.6, 'represented' => true],
                        ['label' => 'B', 'voters' => 2, 'agree_rate' => $scores[$id][0] ?? 0.5, 'necessary_rate' => 0.5, 'represented' => true],
                    ],
                ], $ids),
            ]);
        }]);
    }

    /** @return array<int, array{0: float|null, 1: float|null}> */
    private function scores(): array
    {
        [$consensual, $divisive, $lukewarm] = $this->proposals;

        return [$consensual->id => [0.78, 0.04], $divisive->id => [0.12, 0.7], $lukewarm->id => [0.45, 0.2]];
    }

    private function tab(string $tab): TestResponse
    {
        return $this->get(route('themes.show', $this->theme).'?classement='.$tab);
    }

    public function test_desactive_par_defaut_aucun_calcul_ni_affichage(): void
    {
        $this->population();
        Http::fake();

        $this->assertFalse(config('votalis.consensus.enabled'));
        $this->artisan('consensus:compute')->expectsOutputToContain('désactivé')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertDatabaseCount('consensus_runs', 0);

        $this->tab('consensuelles')->assertOk()->assertSee('pas encore activé');
        $this->actingAs($this->voters[1])->get($this->proposals[0]->url())->assertOk()->assertDontSee('Accord par groupe de votants');
    }

    public function test_desactive_les_scores_deja_calcules_ne_sont_pas_affiches(): void
    {
        $this->population();
        $this->enable();
        $this->fakeService($this->scores());
        $this->artisan('consensus:compute')->assertSuccessful();

        config(['votalis.consensus.enabled' => false]);

        $this->assertNull(app(Consensus::class)->activeRun());
        $this->tab('consensuelles')->assertSee('pas encore activé')->assertDontSee('Fiche consensuelle');
    }

    public function test_sous_les_seuils_le_calcul_est_inactif_sans_appel_au_service(): void
    {
        $this->population();
        config(['votalis.consensus.enabled' => true, 'votalis.consensus.min_votes_per_participant' => 2]);
        Http::fake();

        $this->artisan('consensus:compute')->assertSuccessful();

        Http::assertNothingSent();
        $run = ConsensusRun::sole();
        $this->assertSame(ConsensusRunStatus::Inactive, $run->status);
        $this->assertSame(4, $run->participants);
        $this->tab('consensuelles')->assertSee('Pas encore assez de participation');
    }

    public function test_la_charge_envoyee_ne_contient_aucune_donnee_de_compte(): void
    {
        $this->population();
        $this->enable();
        $this->fakeService($this->scores());

        $this->artisan('consensus:compute')->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();
            $this->assertSame(['proposals', 'votes', 'params'], array_keys($data));
            $this->assertSame(array_map(fn (Proposal $p) => $p->id, $this->proposals), $data['proposals']);

            // Quatre votants retenus (le votant occasionnel est écarté), trois votes chacun,
            // identifiés par des rangs 0 à 3 tirés au hasard.
            $this->assertCount(12, $data['votes']);
            $ranks = array_values(array_unique(array_column($data['votes'], 0)));
            sort($ranks);
            $this->assertSame([0, 1, 2, 3], $ranks);
            foreach ($data['votes'] as $vote) {
                $this->assertCount(4, $vote);
            }

            $body = $request->body();
            foreach (['votant-1', 'example.test', 'occasionnel'] as $needle) {
                $this->assertStringNotContainsString($needle, $body);
            }

            return true;
        });
    }

    public function test_scores_enregistres_et_affiches_dans_les_onglets_et_sur_la_fiche(): void
    {
        $this->population();
        $this->enable();
        $this->fakeService($this->scores());

        $this->artisan('consensus:compute')->assertSuccessful();

        $run = ConsensusRun::sole();
        $this->assertSame(ConsensusRunStatus::Computed, $run->status);
        $this->assertSame(2, $run->k);
        $this->assertDatabaseCount('consensus_scores', 3);
        $this->assertSame(64, strlen((string) $run->input_digest));

        $this->tab('consensuelles')->assertOk()
            ->assertSeeInOrder(['Fiche consensuelle', 'Fiche tiède', 'Fiche clivante'])
            ->assertSee('accord d’au moins 78 % dans chacun des 2 groupes de votants', false);

        $this->tab('clivantes')->assertOk()
            ->assertSeeInOrder(['Fiche clivante', 'Fiche tiède', 'Fiche consensuelle'])
            ->assertSee('écart de 70 points entre groupes de votants');

        // Sur la fiche, comme les autres résultats : après le vote seulement.
        $this->get($this->proposals[0]->url())->assertOk()->assertDontSee('Accord par groupe de votants');
        $this->actingAs($this->voters[1])->get($this->proposals[0]->url())->assertOk()
            ->assertSee('Accord par groupe de votants')
            ->assertSee('Groupe A')
            ->assertSee('Accord minimal entre les groupes : 78 %.')
            ->assertDontSee('votant-1 ·');
    }

    public function test_une_panne_du_service_laisse_les_scores_precedents(): void
    {
        $this->population();
        $this->enable();
        $this->fakeService($this->scores());
        $this->artisan('consensus:compute')->assertSuccessful();
        $first = ConsensusRun::sole();

        Http::fake(['embeddings:8000/consensus' => Http::failedConnection()]);
        $this->artisan('consensus:compute')->assertSuccessful();

        $failed = ConsensusRun::query()->latest('id')->firstOrFail();
        $this->assertSame(ConsensusRunStatus::Failed, $failed->status);
        $this->assertSame('Service de calcul indisponible.', $failed->error);
        $this->assertSame($first->id, app(Consensus::class)->activeRun()?->id);
        $this->tab('consensuelles')->assertSee('Fiche consensuelle');
    }

    public function test_une_reponse_invalide_est_refusee(): void
    {
        $this->population();
        $this->enable();
        $this->fakeService([$this->proposals[0]->id => [1.5, 0.1]]);

        $this->artisan('consensus:compute')->assertSuccessful();

        $this->assertSame(ConsensusRunStatus::Failed, ConsensusRun::sole()->status);
        $this->assertDatabaseCount('consensus_scores', 0);
    }

    public function test_des_scores_perimes_ne_sont_plus_affiches(): void
    {
        $this->population();
        $this->enable();
        $this->fakeService($this->scores());
        $this->artisan('consensus:compute')->assertSuccessful();

        $this->travel(25)->hours();

        $this->assertNull(app(Consensus::class)->activeRun());
        $this->tab('consensuelles')->assertSee('Pas encore assez de participation')->assertDontSee('Fiche consensuelle');
    }

    public function test_seuls_les_derniers_calculs_gardent_leurs_scores(): void
    {
        $this->population();
        $this->enable();
        config(['votalis.consensus.keep_runs' => 2]);
        $this->fakeService($this->scores());

        foreach (range(1, 3) as $i) {
            $this->artisan('consensus:compute')->assertSuccessful();
        }

        $this->assertDatabaseCount('consensus_runs', 3);
        $this->assertSame(2, DB::table('consensus_scores')->distinct()->count('run_id'));
    }

    public function test_l_export_d_audit_correspond_a_l_empreinte_du_calcul(): void
    {
        $this->population();
        $this->enable();
        $this->fakeService($this->scores());
        $path = tempnam(sys_get_temp_dir(), 'consensus-');

        $this->artisan('consensus:compute', ['--export' => $path])->assertSuccessful();

        $body = (string) file_get_contents($path);
        $this->assertSame(ConsensusRun::sole()->input_digest, hash('sha256', $body));
        $this->assertStringNotContainsString('example.test', $body);
        @unlink($path);
    }

    public function test_page_d_explication_du_classement(): void
    {
        $this->get(route('ranking-explained'))->assertOk()
            ->assertSee('Le classement par consensus')
            ->assertSee('pas encore activé');

        $this->population();
        $this->enable();
        $this->fakeService($this->scores());
        $this->artisan('consensus:compute')->assertSuccessful();

        $this->get(route('ranking-explained'))->assertOk()->assertSee('4 participants retenus, 2 groupes');
    }
}
