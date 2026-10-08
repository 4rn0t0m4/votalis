<?php

namespace Tests\Feature\Account;

use App\Enums\ReportMotive;
use App\Enums\Role;
use App\Enums\VoteValue;
use App\Models\Argument;
use App\Models\ModerationLogEntry;
use App\Models\Proposal;
use App\Models\User;
use App\Services\AccountEraser;
use App\Services\AppealService;
use App\Services\ModerationService;
use App\Services\ReportService;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_suppression_efface_les_votes_et_garde_les_contenus_publies_sans_auteur(): void
    {
        Notification::fake();
        $me = User::factory()->create(['pseudonym' => 'a_supprimer', 'email' => 'moi@example.org', 'created_at' => now()->subMonth()]);
        $editorial = User::factory()->role(Role::Editorial)->withTwoFactor()->create();
        $theirs = Proposal::factory()->create();
        $mine = Proposal::factory()->create(['author_id' => $me->id, 'title' => 'Ma proposition qui reste']);
        $argument = Argument::factory()->create(['author_id' => $me->id, 'proposal_id' => $theirs->id]);
        app(VoteService::class)->cast($me, $theirs, VoteValue::Yes, VoteValue::Yes, 'ma condition', false);
        app(ReportService::class)->report($me, $theirs, ReportMotive::Spam, 'Précision à effacer.');
        $decision = app(ModerationService::class)->hide($editorial, $mine, ReportMotive::OffTopic);
        app(AppealService::class)->file($me, $decision, 'Texte de contestation personnel qui doit disparaître.');
        $this->assertSame(1, $theirs->fresh()->votes_count);

        $this->actingAs($me)->get('/mon-compte/suppression')->assertOk()->assertSee('Supprimer définitivement');
        $this->actingAs($me)->from('/mon-compte/suppression')
            ->delete('/mon-compte', ['current_password' => 'mauvais', 'confirmation' => '1'])
            ->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('users', ['id' => $me->id]);

        $this->actingAs($me)
            ->delete('/mon-compte', ['current_password' => 'mot-de-passe-de-test-123', 'confirmation' => '1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $me->id]);
        $this->assertDatabaseCount('votes', 0);
        $this->assertSame(0, $theirs->fresh()->votes_count);
        $this->assertNull($mine->fresh()->author_id);
        $this->assertSame('Participant supprimé', $mine->fresh()->authorName());
        $this->assertNull($argument->fresh()->author_id);
        $this->assertDatabaseHas('reports', ['target_id' => $theirs->id, 'reporter_id' => null, 'details' => null]);
        $this->assertDatabaseHas('appeals', ['log_entry_id' => $decision->id, 'author_id' => null, 'body' => AccountEraser::ERASED_TEXT]);
        $this->assertSame(1, ModerationLogEntry::query()->where('target_id', $mine->id)->count());

        // Plus de connexion possible avec cet e-mail.
        $this->post('/connexion', ['identifier' => 'moi@example.org', 'password' => 'mot-de-passe-de-test-123'])->assertSessionHasErrors();
    }

    public function test_un_compte_privilegie_ne_se_supprime_pas_en_libre_service(): void
    {
        $moderator = User::factory()->role(Role::Moderator)->withTwoFactor()->create();

        $this->actingAs($moderator)->get('/mon-compte/suppression')->assertOk()->assertSee('rôle privilégié')->assertDontSee('Supprimer définitivement');

        $this->expectException(ValidationException::class);
        app(AccountEraser::class)->erase($moderator);
    }
}
