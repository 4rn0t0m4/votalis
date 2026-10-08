<?php

namespace Tests\Feature\Content;

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_fiche_affiche_le_format_impose_sans_donnee_personnelle(): void
    {
        $author = User::factory()->create(['pseudonym' => 'Citoyenne_42', 'email' => 'secret@example.org']);
        $proposal = Proposal::factory()->create(['author_id' => $author->id, 'title' => 'Plafonner les dépassements']);

        $response = $this->get($proposal->url())->assertOk();

        $response->assertSeeInOrder(['Plafonner les dépassements', 'Contribution citoyenne', 'Citoyenne_42', 'Problème visé', 'Mesure proposée', 'Coût ou impact estimé', 'Sources', 'Pour', 'Contre']);
        $response->assertDontSee('secret@example.org');
        $response->assertDontSee('example.org');
        $response->assertDontSee($author->email_hash);
    }

    public function test_l_url_canonique_est_imposee(): void
    {
        $proposal = Proposal::factory()->create(['title' => 'Créer mille lits']);

        $this->get("/propositions/{$proposal->id}/mauvais-slug")->assertRedirect("/propositions/{$proposal->id}/creer-mille-lits");
        $this->get("/propositions/{$proposal->id}")->assertRedirect("/propositions/{$proposal->id}/creer-mille-lits");
    }

    public function test_une_proposition_masquee_est_remplacee_par_un_bandeau_pour_le_public_mais_pas_pour_les_moderateurs(): void
    {
        $proposal = Proposal::factory()->create(['status' => ProposalStatus::Hidden]);

        $this->get($proposal->url())->assertOk()->assertSee('Contenu masqué')->assertDontSee($proposal->measure);
        $this->actingAs(User::factory()->role(Role::Moderator)->withTwoFactor()->create())->get($proposal->url())->assertOk()->assertSee($proposal->measure);
    }

    public function test_l_historique_des_modifications_est_complet_et_public(): void
    {
        $proposal = Proposal::factory()->create();
        $proposal->revisions()->create(['author_id' => $proposal->author_id, 'kind' => 'content', 'snapshot' => $proposal->load('sources')->snapshot()]);
        $this->travel(1)->minute();

        app(ProposalService::class)->update($proposal, [
            'theme_id' => $proposal->theme_id, 'title' => $proposal->title, 'problem' => 'Un problème reformulé en profondeur.',
            'measure' => $proposal->measure, 'cost_estimate' => $proposal->cost_estimate, 'cost_unknown' => false,
            'sources' => ['https://www.exemple.gouv.fr/rapport'], 'personal_source' => false,
        ], $proposal->author);

        $this->get($proposal->url())->assertOk()->assertSeeInOrder(['Historique des modifications', 'Rédaction', 'Publication']);
        $this->assertCount(2, $proposal->fresh()->revisions);
    }

    public function test_une_fiche_d_amorcage_affiche_son_origine(): void
    {
        $proposal = Proposal::factory()->seed()->create(['seed_source' => 'Rapport de la Cour des comptes 2024']);

        $this->get($proposal->url())->assertOk()->assertSee("Contenu d'amorçage")->assertSee('Rapport de la Cour des comptes 2024')->assertSee('Comité éditorial');
    }
}
