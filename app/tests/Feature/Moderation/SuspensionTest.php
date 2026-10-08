<?php

namespace Tests\Feature\Moderation;

use App\Enums\ModerationAction;
use App\Enums\ReportMotive;
use App\Enums\Role;
use App\Models\Argument;
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

class SuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seul_le_comite_suspend_et_seulement_un_compte_participant(): void
    {
        Notification::fake();
        $participant = User::factory()->create(['pseudonym' => 'compte_a_suspendre']);
        $moderator = User::factory()->role(Role::Moderator)->withTwoFactor()->create();
        $editorial = User::factory()->role(Role::Editorial)->withTwoFactor()->create();
        $service = app(ModerationService::class);

        try {
            $service->suspend($moderator, $participant, ReportMotive::CoordinatedCampaign);
            $this->fail('Un modérateur ne suspend pas.');
        } catch (AuthorizationException) {
        }

        try {
            $service->suspend($editorial, $moderator, ReportMotive::CoordinatedCampaign);
            $this->fail('Un compte privilégié ne se suspend pas depuis l’interface.');
        } catch (ValidationException) {
        }

        $entry = $service->suspend($editorial, $participant, ReportMotive::CoordinatedCampaign, 30);

        $participant->refresh();
        $this->assertTrue($participant->isSuspended());
        $this->assertSame(ModerationAction::Suspend, $entry->action);
        $this->assertSame(['days' => 30], $entry->details);
        Notification::assertSentTo($participant, ModerationNotice::class, fn (ModerationNotice $n) => $n->kind === ModerationNotice::ACCOUNT);

        // Journal public : « Compte suspendu », sans pseudonyme ni lien.
        $this->get('/journal-de-moderation')->assertOk()->assertSee('Compte suspendu')->assertSee('Campagne coordonnée')->assertDontSee('compte_a_suspendre');
        $this->assertSame('Compte', $entry->targetLabel());
    }

    public function test_un_compte_suspendu_lit_et_conteste_mais_ne_contribue_plus(): void
    {
        Notification::fake();
        $participant = User::factory()->create(['created_at' => now()->subMonth()]);
        $editorial = User::factory()->role(Role::Editorial)->withTwoFactor()->create();
        $proposal = Proposal::factory()->create();
        $entry = app(ModerationService::class)->suspend($editorial, $participant, ReportMotive::Spam);
        $participant->refresh();

        $this->assertFalse($participant->can('participate'));
        $this->assertFalse($participant->can('vote', $proposal));
        $this->assertFalse($participant->can('create', Proposal::class));
        $this->assertFalse($participant->can('create', Argument::class));
        $this->assertFalse($participant->can('report', $proposal));
        $this->actingAs($participant)->get('/propositions/nouvelle')->assertForbidden();
        $this->actingAs($participant)->get($proposal->url())->assertOk();
        $this->actingAs($participant)->get('/mon-compte')->assertOk()->assertSee('Votre compte est suspendu');
        $this->actingAs($participant)->get("/mon-compte/moderation/contester/{$entry->id}")->assertOk();

        $appeal = app(AppealService::class)->file($participant, $entry, 'Je n’ai publié aucun contenu répété ; mes contributions sont argumentées.');
        app(AppealService::class)->decide(User::factory()->role(Role::Editorial)->withTwoFactor()->create(), $appeal, true);

        $participant->refresh();
        $this->assertFalse($participant->isSuspended());
        $this->assertTrue($participant->can('participate'));
        $this->assertDatabaseHas('moderation_log', ['target_type' => 'user', 'target_id' => $participant->id, 'action' => ModerationAction::AppealOverturned->value]);
    }

    public function test_une_suspension_a_terme_se_leve_seule(): void
    {
        Notification::fake();
        $participant = User::factory()->create();
        $editorial = User::factory()->role(Role::Editorial)->withTwoFactor()->create();
        app(ModerationService::class)->suspend($editorial, $participant, ReportMotive::Spam, 7);

        $this->assertTrue($participant->fresh()->isSuspended());
        $this->travel(8)->days();
        $this->assertFalse($participant->fresh()->isSuspended());
    }

    public function test_la_suspension_se_fait_depuis_le_dossier_par_le_comite(): void
    {
        Notification::fake();
        $proposal = Proposal::factory()->create();
        $moderator = User::factory()->role(Role::Moderator)->withTwoFactor()->create();
        $editorial = User::factory()->role(Role::Editorial)->withTwoFactor()->create();

        $this->actingAs($moderator)->get("/moderation/dossiers/proposition/{$proposal->id}")->assertOk()->assertDontSee('Suspendre le compte');
        $this->actingAs($moderator)->post("/moderation/dossiers/proposition/{$proposal->id}/suspendre", ['motive' => 'spam'])->assertForbidden();
        $this->actingAs($editorial)->get("/moderation/dossiers/proposition/{$proposal->id}")->assertOk()->assertSee('Suspendre le compte');
        $this->actingAs($editorial)->post("/moderation/dossiers/proposition/{$proposal->id}/suspendre", ['motive' => 'spam', 'days' => 10])->assertRedirect("/moderation/dossiers/proposition/{$proposal->id}");

        $this->assertTrue($proposal->author->fresh()->isSuspended());
        $this->assertSame(1, ModerationLogEntry::query()->where('action', 'suspend')->count());
    }
}
