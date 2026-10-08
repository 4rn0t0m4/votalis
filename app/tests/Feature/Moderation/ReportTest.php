<?php

namespace Tests\Feature\Moderation;

use App\Enums\ArgumentStatus;
use App\Enums\ModerationAction;
use App\Enums\ProposalStatus;
use App\Enums\ReportMotive;
use App\Enums\Role;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\User;
use App\Services\QuickVoteSelector;
use App\Services\ReportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create(['created_at' => now()->subMonth()]);
    }

    public function test_seul_un_participant_verifie_peut_signaler(): void
    {
        $proposal = Proposal::factory()->create();

        $this->get("/signaler/proposition/{$proposal->id}")->assertRedirect('/connexion');
        $this->actingAs(User::factory()->unverified()->create())->get("/signaler/proposition/{$proposal->id}")->assertRedirect('/verifier-email');
        $this->actingAs(User::factory()->role(Role::Admin)->withTwoFactor()->create())->get("/signaler/proposition/{$proposal->id}")->assertForbidden();
        $this->actingAs($this->participant())->get("/signaler/proposition/{$proposal->id}")->assertOk()->assertSee('Contenu illégal')->assertSee('Campagne coordonnée');
    }

    public function test_un_signalement_ordinaire_laisse_le_contenu_visible(): void
    {
        $proposal = Proposal::factory()->create();
        $reporter = $this->participant();

        $this->actingAs($reporter)
            ->post("/signaler/proposition/{$proposal->id}", ['motive' => 'spam', 'details' => 'Publicité déguisée.'])
            ->assertRedirect($proposal->url());

        $this->assertDatabaseHas('reports', ['target_type' => 'proposal', 'target_id' => $proposal->id, 'reporter_id' => $reporter->id, 'motive' => 'spam', 'status' => 'open']);
        $this->assertSame(ProposalStatus::Published, $proposal->fresh()->status);
        $this->assertDatabaseCount('moderation_log', 0);
        $this->get($proposal->url())->assertOk()->assertSee($proposal->title);
    }

    public function test_un_signalement_pour_contenu_illegal_masque_immediatement_et_journalise(): void
    {
        $proposal = Proposal::factory()->create(['title' => 'Titre à ne plus montrer']);
        $argument = Argument::factory()->create(['proposal_id' => Proposal::factory()->create()->id, 'body' => 'Argument à ne plus montrer du tout.']);

        $service = app(ReportService::class);
        $service->report($this->participant(), $proposal, ReportMotive::Illegal);
        $service->report($this->participant(), $argument, ReportMotive::Illegal);

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Hidden, $proposal->status);
        $this->assertSame(ReportMotive::Illegal, $proposal->hidden_motive);
        $this->assertSame(ArgumentStatus::Hidden, $argument->fresh()->status);
        $this->assertDatabaseHas('moderation_log', ['target_type' => 'proposal', 'target_id' => $proposal->id, 'action' => ModerationAction::AutoHide->value, 'motive' => 'illegal', 'actor_role' => 'system']);

        // Public : bandeau sans le titre ; auteur et modérateur : contenu complet.
        $this->get("/propositions/{$proposal->id}")->assertOk()->assertSee('Contenu masqué')->assertDontSee('Titre à ne plus montrer');
        $this->actingAs($proposal->author)->get($proposal->url())->assertOk()->assertSee('Titre à ne plus montrer')->assertSee('masquée par la modération');
        $this->actingAs(User::factory()->role(Role::Moderator)->withTwoFactor()->create())->get($proposal->url())->assertOk()->assertSee('Titre à ne plus montrer');

        // L'argument masqué est remplacé par un bandeau, sans son texte ni son motif détaillé.
        $this->get($argument->proposal->url())->assertOk()->assertSee('Argument masqué par la modération')->assertDontSee('Argument à ne plus montrer');
    }

    public function test_un_contenu_masque_pour_un_autre_motif_affiche_le_titre_et_le_motif(): void
    {
        $proposal = Proposal::factory()->create(['title' => 'Fiche hors sujet', 'status' => ProposalStatus::Hidden, 'hidden_motive' => ReportMotive::OffTopic]);

        $this->get("/propositions/{$proposal->id}")->assertOk()->assertSee('Fiche hors sujet')->assertSee('Hors sujet')->assertDontSee($proposal->measure);
    }

    public function test_un_seul_signalement_par_compte_et_par_contenu(): void
    {
        $proposal = Proposal::factory()->create();
        $reporter = $this->participant();
        $service = app(ReportService::class);

        $service->report($reporter, $proposal, ReportMotive::Spam);

        $this->expectException(ValidationException::class);
        $service->report($reporter, $proposal, ReportMotive::OffTopic);
    }

    public function test_on_ne_signale_ni_son_propre_contenu_ni_un_contenu_deja_masque(): void
    {
        $author = $this->participant();
        $own = Proposal::factory()->create(['author_id' => $author->id]);
        $hidden = Proposal::factory()->create(['status' => ProposalStatus::Hidden, 'hidden_motive' => ReportMotive::Spam]);
        $service = app(ReportService::class);

        try {
            $service->report($author, $own, ReportMotive::Spam);
            $this->fail('Signaler sa propre fiche doit être refusé.');
        } catch (AuthorizationException) {
        }

        $this->expectException(AuthorizationException::class);
        $service->report($author, $hidden, ReportMotive::Spam);
    }

    public function test_le_plafond_de_signalements_est_applique_cote_serveur(): void
    {
        config(['votalis.caps.reports_per_day' => 2]);
        $reporter = $this->participant();
        $service = app(ReportService::class);

        $service->report($reporter, Proposal::factory()->create(), ReportMotive::Spam);
        $service->report($reporter, Proposal::factory()->create(), ReportMotive::Spam);

        $this->actingAs($reporter)
            ->from('/')
            ->post('/signaler/proposition/'.Proposal::factory()->create()->id, ['motive' => 'spam'])
            ->assertSessionHasErrors('cap');
        $this->assertDatabaseCount('reports', 2);
    }

    public function test_un_contenu_masque_sort_des_listes_du_vote_rapide_et_de_la_recherche(): void
    {
        $proposal = Proposal::factory()->create(['title' => 'Mesure masquée unique']);
        app(ReportService::class)->report($this->participant(), $proposal, ReportMotive::Illegal);

        $this->get(route('themes.show', $proposal->theme))->assertOk()->assertDontSee('Mesure masquée unique');
        $this->assertNull(app(QuickVoteSelector::class)->next($this->participant()));
        $this->assertFalse($proposal->fresh()->shouldBeSearchable());
    }
}
