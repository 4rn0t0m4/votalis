<?php

namespace Tests\Feature\Moderation;

use App\Enums\ArgumentStatus;
use App\Enums\ModerationAction;
use App\Enums\ProposalStatus;
use App\Enums\ReportMotive;
use App\Enums\ReportStatus;
use App\Enums\RevisionKind;
use App\Enums\Role;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ModerationQueue;
use App\Services\ModerationService;
use App\Services\ProposalService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModerationQueueTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create(['created_at' => now()->subMonth()]);
    }

    private function moderator(): User
    {
        return User::factory()->role(Role::Moderator)->withTwoFactor()->create();
    }

    public function test_la_file_est_reservee_aux_moderateurs_et_au_comite(): void
    {
        $this->get('/moderation')->assertRedirect('/connexion');
        $this->actingAs($this->participant())->get('/moderation')->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Admin)->withTwoFactor()->create())->get('/moderation')->assertForbidden();
        $this->actingAs($this->moderator())->get('/moderation')->assertOk();
        $this->actingAs(User::factory()->role(Role::Editorial)->withTwoFactor()->create())->get('/moderation')->assertOk();
        // Sans second facteur, le modérateur est renvoyé vers la page de sécurité.
        $this->actingAs(User::factory()->role(Role::Moderator)->create())->get('/moderation')->assertRedirect('/mon-compte/securite');
    }

    public function test_la_file_est_triee_par_gravite_puis_par_anciennete(): void
    {
        $service = app(ReportService::class);
        $old = Proposal::factory()->create(['title' => 'Spam ancien']);
        $recent = Proposal::factory()->create(['title' => 'Spam récent']);
        $grave = Proposal::factory()->create(['title' => 'Attaque récente']);

        $this->travelTo(now()->subHours(3));
        $service->report($this->participant(), $old, ReportMotive::Spam);
        $this->travelBack();
        $service->report($this->participant(), $recent, ReportMotive::Spam);
        $service->report($this->participant(), $grave, ReportMotive::PersonalAttack);
        // Un second signalement « spam » sur une fiche déjà signalée ne change pas sa gravité mais compte.
        $service->report($this->participant(), $recent, ReportMotive::OffTopic);

        $cases = app(ModerationQueue::class)->cases();

        $this->assertSame(['Attaque récente', 'Spam ancien', 'Spam récent'], $cases->map(fn ($c) => $c['target']->title)->all());
        $this->assertSame(2, $cases[2]['count']);
        $this->assertSame(3, app(ModerationQueue::class)->openCount());

        $this->actingAs($this->moderator())->get('/moderation')->assertOk()->assertSeeInOrder(['Attaque récente', 'Spam ancien', 'Spam récent']);
    }

    public function test_le_dossier_montre_le_contexte_sans_identifier_le_signaleur(): void
    {
        $reporter = User::factory()->create(['pseudonym' => 'signaleuse_discrete', 'email' => 'signaleuse@example.org']);
        $proposal = Proposal::factory()->create();
        app(ReportService::class)->report($reporter, $proposal, ReportMotive::Disinformation, 'Chiffre inventé.');

        $this->actingAs($this->moderator())
            ->get("/moderation/dossiers/proposition/{$proposal->id}")
            ->assertOk()
            ->assertSee($proposal->title)
            ->assertSee($proposal->author->pseudonym)
            ->assertSee('Désinformation manifeste')
            ->assertSee('Chiffre inventé.')
            ->assertSee('1 signalement au total')
            ->assertDontSee('signaleuse_discrete')
            ->assertDontSee('signaleuse@example.org');
    }

    public function test_conserver_clot_les_signalements_et_retablit_un_contenu_masque_en_attente(): void
    {
        $proposal = Proposal::factory()->create();
        app(ReportService::class)->report($this->participant(), $proposal, ReportMotive::Illegal);
        $this->assertSame(ProposalStatus::Hidden, $proposal->fresh()->status);

        $this->actingAs($this->moderator())
            ->post("/moderation/dossiers/proposition/{$proposal->id}/conserver")
            ->assertRedirect('/moderation');

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Published, $proposal->status);
        $this->assertNull($proposal->hidden_motive);
        $this->assertSame(ReportStatus::Handled, $proposal->reports()->sole()->status);
        $this->assertDatabaseHas('moderation_log', ['target_id' => $proposal->id, 'action' => ModerationAction::Keep->value, 'actor_role' => 'moderator']);
        $this->assertNotNull($proposal->reports()->sole()->log_entry_id);
    }

    public function test_masquer_avec_motif_journalise_et_reference_la_fiche_conservee_pour_un_doublon(): void
    {
        $kept = Proposal::factory()->create();
        $duplicate = Proposal::factory()->create();
        $argument = Argument::factory()->create();
        app(ReportService::class)->report($this->participant(), $duplicate, ReportMotive::Duplicate);
        $moderator = $this->moderator();

        // Un doublon exige la fiche conservée.
        $this->actingAs($moderator)->from('/moderation')
            ->post("/moderation/dossiers/proposition/{$duplicate->id}/masquer", ['motive' => 'duplicate'])
            ->assertSessionHasErrors('kept_proposal_id');
        $this->assertSame(ProposalStatus::Published, $duplicate->fresh()->status);

        $this->actingAs($moderator)
            ->post("/moderation/dossiers/proposition/{$duplicate->id}/masquer", ['motive' => 'duplicate', 'kept_proposal_id' => $kept->id])
            ->assertRedirect('/moderation');
        $this->assertSame(ProposalStatus::Hidden, $duplicate->fresh()->status);
        $this->assertSame(ReportMotive::Duplicate, $duplicate->fresh()->hidden_motive);
        $entry = $duplicate->moderationEntries()->first();
        $this->assertSame(ModerationAction::Hide, $entry->action);
        $this->assertSame(['kept_proposal_id' => $kept->id], $entry->details);

        $this->actingAs($this->participant())->get($duplicate->url())->assertOk()->assertSee('Fiche conservée')->assertSee($kept->title);

        app(ModerationService::class)->hide($moderator, $argument, ReportMotive::PersonalAttack);
        $this->assertSame(ArgumentStatus::Hidden, $argument->fresh()->status);
        $this->assertDatabaseHas('moderation_log', ['target_type' => 'argument', 'target_id' => $argument->id, 'action' => 'hide', 'motive' => 'personal_attack']);
    }

    public function test_une_reformulation_rouvre_le_fond_a_l_auteur_une_fois_malgre_le_verrou(): void
    {
        $author = $this->participant();
        $proposal = Proposal::factory()->locked()->create(['author_id' => $author->id, 'measure' => 'Mesure initiale, formulée de façon trop vague pour être évaluée.']);
        $moderator = $this->moderator();

        $this->actingAs($moderator)
            ->post("/moderation/dossiers/proposition/{$proposal->id}/reformulation", ['motive' => 'off_topic'])
            ->assertRedirect('/moderation');

        $proposal->refresh();
        $this->assertSame(ProposalStatus::RewriteRequested, $proposal->status);
        $this->assertNotNull($proposal->rewrite_allowed_until);
        $this->actingAs($this->participant())->get($proposal->url())->assertOk()->assertSee('en attente de reformulation')->assertDontSee('Mesure initiale');

        // Un autre participant ne peut pas modifier ; l'auteur, si, et sur le fond.
        $this->assertFalse($this->participant()->can('update', $proposal));
        $this->assertTrue($author->can('update', $proposal));

        $input = [
            'theme_id' => $proposal->theme_id,
            'title' => $proposal->title,
            'problem' => $proposal->problem,
            'measure' => 'Mesure entièrement réécrite avec un dispositif précis, un calendrier et un périmètre clairement délimité.',
            'cost_estimate' => null,
            'cost_unknown' => true,
            'sources' => ['https://www.exemple.gouv.fr/rapport'],
            'personal_source' => false,
        ];
        app(ProposalService::class)->update($proposal, $input, $author);

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Published, $proposal->status);
        $this->assertNull($proposal->hidden_motive);
        $this->assertNull($proposal->rewrite_allowed_until);
        $this->assertStringStartsWith('Mesure entièrement réécrite', $proposal->measure);
        $this->assertSame(RevisionKind::Content, $proposal->revisions()->first()->kind);
        $this->assertDatabaseHas('moderation_log', ['target_id' => $proposal->id, 'action' => ModerationAction::RewriteReceived->value, 'actor_role' => 'system']);

        // Le verrou s'applique de nouveau : une seconde modification de fond est refusée.
        $this->expectException(ValidationException::class);
        app(ProposalService::class)->update($proposal, [...$input, 'measure' => 'Encore une autre mesure, complètement différente de la précédente et bien plus longue.'], $author);
    }

    public function test_une_reformulation_expiree_ne_rouvre_plus_la_fiche(): void
    {
        $author = $this->participant();
        $proposal = Proposal::factory()->create(['author_id' => $author->id]);
        app(ModerationService::class)->requestRewrite($this->moderator(), $proposal, ReportMotive::OffTopic);

        $this->travel((int) config('votalis.moderation.rewrite_days') + 1)->days();

        $this->assertFalse($author->can('update', $proposal->fresh()));
    }

    public function test_les_actions_de_moderation_sont_refusees_a_un_participant(): void
    {
        $proposal = Proposal::factory()->create();

        $this->actingAs($this->participant())->post("/moderation/dossiers/proposition/{$proposal->id}/masquer", ['motive' => 'spam'])->assertForbidden();
        $this->assertSame(ProposalStatus::Published, $proposal->fresh()->status);
        $this->assertDatabaseCount('moderation_log', 0);
    }
}
