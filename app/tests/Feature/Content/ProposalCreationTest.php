<?php

namespace Tests\Feature\Content;

use App\Enums\ProposalOrigin;
use App\Enums\Role;
use App\Livewire\ProposalForm;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use App\Services\ProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProposalCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function valid(Theme $theme, array $overrides = []): array
    {
        return array_merge([
            'theme_id' => $theme->id,
            'title' => 'Plafonner les dépassements d’honoraires',
            'problem' => 'Les dépassements creusent les inégalités d’accès aux soins.',
            'measure' => 'Plafonner les dépassements à 50 % du tarif conventionnel, avec une période de transition de trois ans.',
            'cost_estimate' => 'Neutre pour l’assurance maladie',
            'cost_unknown' => false,
            'sources' => ['https://www.ccomptes.fr/rapport'],
            'personal_source' => false,
        ], $overrides);
    }

    public function test_un_participant_verifie_publie_une_fiche_complete(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $theme = Theme::factory()->create();

        Livewire::actingAs($user)->test(ProposalForm::class)
            ->set('theme_id', $theme->id)
            ->set('title', 'Plafonner les dépassements d’honoraires')
            ->set('problem', 'Les dépassements creusent les inégalités d’accès aux soins.')
            ->set('measure', 'Plafonner les dépassements à 50 % du tarif conventionnel.')
            ->set('cost_estimate', 'Neutre pour l’assurance maladie')
            ->set('sources', ['https://www.ccomptes.fr/rapport', ''])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $proposal = Proposal::sole();
        $this->assertSame($user->id, $proposal->author_id);
        $this->assertSame(ProposalOrigin::Citizen, $proposal->origin);
        $this->assertCount(1, $proposal->sources);
        $this->assertCount(1, $proposal->revisions);
        $this->assertSame('Plafonner les dépassements d’honoraires', $proposal->revisions->first()?->snapshot['title']);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function fichesIncompletes(): array
    {
        return [
            'titre manquant' => [['title' => ''], 'title'],
            'titre trop long' => [['title' => str_repeat('a', 121)], 'title'],
            'problème manquant' => [['problem' => ''], 'problem'],
            'problème trop long' => [['problem' => str_repeat('a', 501)], 'problem'],
            'mesure manquante' => [['measure' => ''], 'measure'],
            'mesure trop longue' => [['measure' => str_repeat('a', 1501)], 'measure'],
            'coût manquant sans case inconnu' => [['cost_estimate' => '', 'cost_unknown' => false], 'cost_estimate'],
            'aucune source' => [['sources' => [], 'personal_source' => false], 'sources'],
            'source invalide' => [['sources' => ['pas une url']], 'sources.0'],
            'thème manquant' => [['theme_id' => null], 'theme_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('fichesIncompletes')]
    public function test_une_fiche_incomplete_est_refusee_avec_un_message_clair(array $overrides, string $field): void
    {
        $theme = Theme::factory()->create();

        try {
            app(ProposalService::class)->create($this->valid($theme, $overrides), User::factory()->create());
            $this->fail('La fiche aurait dû être refusée.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
            $this->assertNotEmpty($e->errors()[$field][0]);
            $this->assertDoesNotMatchRegularExpression('/validation\./', $e->errors()[$field][0], 'Le message doit être traduit.');
        }

        $this->assertDatabaseCount('proposals', 0);
    }

    public function test_le_cout_inconnu_et_la_proposition_personnelle_sont_acceptes(): void
    {
        $theme = Theme::factory()->create();
        $proposal = app(ProposalService::class)->create($this->valid($theme, [
            'cost_estimate' => '', 'cost_unknown' => true, 'sources' => [], 'personal_source' => true,
        ]), User::factory()->create());

        $this->assertTrue($proposal->cost_unknown);
        $this->assertNull($proposal->cost_estimate);
        $this->assertTrue($proposal->sources->sole()->is_personal);
    }

    public function test_un_theme_ferme_ou_archive_refuse_une_proposition_meme_par_requete_directe(): void
    {
        foreach ([Theme::factory()->closed()->create(), Theme::factory()->archived()->create()] as $theme) {
            try {
                app(ProposalService::class)->create($this->valid($theme), User::factory()->create());
                $this->fail('Le thème aurait dû refuser la proposition.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('theme_id', $e->errors());
            }
        }

        $this->assertDatabaseCount('proposals', 0);
    }

    public function test_seul_un_participant_verifie_peut_deposer(): void
    {
        $theme = Theme::factory()->create();

        $this->get('/propositions/nouvelle')->assertRedirect('/connexion');
        $this->actingAs(User::factory()->unverified()->create())->get('/propositions/nouvelle')->assertRedirect('/verifier-email');
        $this->actingAs(User::factory()->role(Role::Admin)->withTwoFactor()->create())->get('/propositions/nouvelle')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/propositions/nouvelle?theme='.$theme->id)->assertOk()->assertSee('Proposer une mesure');
    }

    public function test_le_plafond_de_propositions_par_mois_et_par_theme_est_applique_cote_serveur(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonths(2)]);
        $theme = Theme::factory()->create();
        $other = Theme::factory()->create();
        $service = app(ProposalService::class);

        for ($i = 0; $i < 3; $i++) {
            $service->create($this->valid($theme, ['title' => "Mesure {$i}"]), $user);
        }

        try {
            $service->create($this->valid($theme, ['title' => 'Mesure de trop']), $user);
            $this->fail('Le plafond aurait dû bloquer.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cap', $e->errors());
        }

        $service->create($this->valid($other, ['title' => 'Autre thème']), $user);
        $this->assertDatabaseCount('proposals', 4);
    }

    public function test_un_compte_recent_a_un_plafond_divise_par_deux(): void
    {
        $user = User::factory()->create(['created_at' => now()->subDay()]);
        $theme = Theme::factory()->create();
        $service = app(ProposalService::class);

        $service->create($this->valid($theme), $user);

        $this->expectException(ValidationException::class);
        $service->create($this->valid($theme, ['title' => 'Deuxième']), $user);
    }
}
