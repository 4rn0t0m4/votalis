<?php

namespace Tests\Feature\Moderation;

use App\Enums\AppealStatus;
use App\Enums\ModerationAction;
use App\Enums\ProposalStatus;
use App\Enums\ReportMotive;
use App\Enums\Role;
use App\Models\Appeal;
use App\Models\ModerationLogEntry;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\ModerationNotice;
use App\Services\AppealService;
use App\Services\ModerationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppealTest extends TestCase
{
    use RefreshDatabase;

    private function editorial(): User
    {
        return User::factory()->role(Role::Editorial)->withTwoFactor()->create();
    }

    /** @return array{0: User, 1: Proposal, 2: ModerationLogEntry, 3: User} auteur, fiche, décision, décideur */
    private function hiddenProposal(): array
    {
        Notification::fake();
        $author = User::factory()->create(['created_at' => now()->subMonth()]);
        $proposal = Proposal::factory()->create(['author_id' => $author->id]);
        $decider = $this->editorial();
        $entry = app(ModerationService::class)->hide($decider, $proposal, ReportMotive::OffTopic);

        return [$author, $proposal, $entry, $decider];
    }

    public function test_d2_un_membre_ne_tranche_jamais_la_contestation_de_sa_propre_decision(): void
    {
        [$author, $proposal, $entry, $decider] = $this->hiddenProposal();
        $appeal = app(AppealService::class)->file($author, $entry, 'La mesure porte précisément sur le thème : voir le problème visé et la source citée.');

        // Par le service, puis par la route HTTP.
        try {
            app(AppealService::class)->decide($decider, $appeal, true);
            $this->fail('Le décideur ne doit pas trancher sa propre décision.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('appeal', $e->errors());
        }

        $this->actingAs($decider)->from("/moderation/contestations/{$appeal->id}")
            ->post("/moderation/contestations/{$appeal->id}", ['decision' => 'overturn'])
            ->assertSessionHasErrors('appeal');

        $this->assertSame(AppealStatus::Pending, $appeal->fresh()->status);
        $this->assertSame(ProposalStatus::Hidden, $proposal->fresh()->status);

        // Un autre membre du comité peut.
        $this->actingAs($this->editorial())->get("/moderation/contestations/{$appeal->id}")->assertOk()->assertSee('Enregistrer la décision');
        $this->actingAs($decider)->get("/moderation/contestations/{$appeal->id}")->assertOk()->assertSee('un autre membre du comité doit trancher');
        $this->actingAs($this->editorial())->post("/moderation/contestations/{$appeal->id}", ['decision' => 'overturn', 'decision_note' => 'La fiche est bien dans le thème.'])->assertRedirect('/moderation/contestations');
        $this->assertSame(AppealStatus::Overturned, $appeal->fresh()->status);
    }

    public function test_l_auteur_conteste_une_fois_dans_le_delai_et_les_autres_ne_peuvent_pas(): void
    {
        [$author, $proposal, $entry] = $this->hiddenProposal();
        $other = User::factory()->create();

        $this->actingAs($other)->get("/mon-compte/moderation/contester/{$entry->id}")->assertForbidden();
        $this->actingAs($author)->get("/mon-compte/moderation/contester/{$entry->id}")->assertOk()->assertSee($proposal->title);

        $this->actingAs($author)->post("/mon-compte/moderation/contester/{$entry->id}", ['body' => 'Trop court'])->assertSessionHasErrors('body');
        $this->actingAs($author)->post("/mon-compte/moderation/contester/{$entry->id}", ['body' => 'Cette décision ne respecte pas la charte : la mesure est bien dans le thème.'])->assertRedirect('/mon-compte/moderation');
        $this->assertDatabaseCount('appeals', 1);

        // Une seule fois.
        $this->actingAs($author)->get("/mon-compte/moderation/contester/{$entry->id}")->assertForbidden();

        // Hors délai.
        $late = app(ModerationService::class)->hide($this->editorial(), Proposal::factory()->create(['author_id' => $author->id]), ReportMotive::Spam);
        $this->travel((int) config('votalis.moderation.appeal_days') + 1)->days();
        $this->expectException(AuthorizationException::class);
        app(AppealService::class)->file($author, $late, 'Contestation déposée trop tard pour être recevable, en principe.');
    }

    public function test_une_decision_annulee_retablit_le_contenu_et_une_decision_confirmee_ne_change_rien(): void
    {
        [$author, $proposal, $entry] = $this->hiddenProposal();
        $service = app(AppealService::class);
        $appeal = $service->file($author, $entry, 'La fiche cite une source officielle et traite exactement du thème.');

        $service->decide($this->editorial(), $appeal, true, 'Annulée : la fiche respecte la charte.');

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Published, $proposal->status);
        $this->assertNull($proposal->hidden_motive);
        $decision = ModerationLogEntry::query()->where('action', ModerationAction::AppealOverturned->value)->sole();
        $this->assertSame(['appealed_entry_id' => $entry->id], $decision->details);
        $this->assertSame('editorial', $decision->actor_role->value);
        $this->assertSame($decision->id, $appeal->fresh()->decision_log_entry_id);
        Notification::assertSentTo($author, ModerationNotice::class, fn (ModerationNotice $n) => $n->kind === ModerationNotice::APPEAL_DECIDED);

        // Confirmation sur une autre décision.
        $second = app(ModerationService::class)->hide($this->editorial(), Proposal::factory()->create(['author_id' => $author->id]), ReportMotive::Spam);
        $appeal2 = $service->file($author, $second, 'Ce n’est pas du spam : la proposition est argumentée et sourcée.');
        $service->decide($this->editorial(), $appeal2, false);
        $this->assertSame(ProposalStatus::Hidden, $second->target->fresh()->status);
        $this->assertSame(AppealStatus::Confirmed, $appeal2->fresh()->status);
        $this->assertDatabaseHas('moderation_log', ['action' => ModerationAction::AppealConfirmed->value, 'target_id' => $second->target_id]);

        // L'auteur voit l'issue sur son compte, avec la motivation.
        $this->actingAs($author)->get('/mon-compte/moderation')->assertOk()
            ->assertSee('Décision annulée, contenu rétabli')->assertSee('Annulée : la fiche respecte la charte.')->assertSee('Décision confirmée');
    }

    public function test_l_auteur_est_prevenu_par_un_e_mail_sans_contenu_ni_motif(): void
    {
        [$author, $proposal] = $this->hiddenProposal();

        Notification::assertSentTo($author, ModerationNotice::class, function (ModerationNotice $notice) use ($author, $proposal): bool {
            $mail = $notice->toMail($author)->render()->toHtml();
            $this->assertStringNotContainsString($proposal->title, $mail);
            $this->assertStringNotContainsString('Hors sujet', $mail);
            $this->assertStringNotContainsString($author->pseudonym, $mail);
            $this->assertStringContainsString('/mon-compte/moderation', $mail);

            return $notice->kind === ModerationNotice::DECISION;
        });
    }

    public function test_la_page_des_contestations_est_reservee_au_comite(): void
    {
        $this->actingAs(User::factory()->role(Role::Moderator)->withTwoFactor()->create())->get('/moderation/contestations')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/moderation/contestations')->assertForbidden();
        $this->actingAs($this->editorial())->get('/moderation/contestations')->assertOk()->assertSee('Aucune contestation en attente');
        Appeal::query()->count();
    }
}
