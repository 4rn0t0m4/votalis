<?php

namespace Tests\Feature\Moderation;

use App\Enums\ActorRole;
use App\Enums\ModerationAction;
use App\Enums\ReportMotive;
use App\Enums\Role;
use App\Models\ModerationLogEntry;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class ModerationLogTest extends TestCase
{
    use RefreshDatabase;

    private function entry(): ModerationLogEntry
    {
        return ModerationLogEntry::create([
            'target_type' => 'proposal',
            'target_id' => Proposal::factory()->create()->id,
            'action' => ModerationAction::Keep,
            'actor_id' => null,
            'actor_role' => ActorRole::System,
        ]);
    }

    public function test_d1_une_entree_ne_peut_etre_ni_modifiee_ni_supprimee_meme_en_base(): void
    {
        $entry = $this->entry();

        foreach ([
            "UPDATE moderation_log SET action = 'hide' WHERE id = {$entry->id}",
            "DELETE FROM moderation_log WHERE id = {$entry->id}",
            'TRUNCATE moderation_log CASCADE',
        ] as $sql) {
            // Point de sauvegarde : une erreur PostgreSQL avorte sinon toute la transaction du test.
            DB::beginTransaction();
            try {
                DB::statement($sql);
                $this->fail("La base doit refuser : {$sql}");
            } catch (QueryException $e) {
                $this->assertStringContainsString('ajout seul', $e->getMessage());
            } finally {
                DB::rollBack();
            }
        }

        $this->assertDatabaseHas('moderation_log', ['id' => $entry->id, 'action' => 'keep']);
    }

    public function test_le_modele_refuse_aussi_de_modifier_ou_supprimer(): void
    {
        $entry = $this->entry();

        try {
            $entry->action = ModerationAction::Hide;
            $entry->save();
            $this->fail('Le modèle doit refuser la modification.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $entry->delete();
    }

    public function test_le_journal_est_public_et_ne_reproduit_ni_contenu_illegal_ni_identite(): void
    {
        $moderator = User::factory()->role(Role::Moderator)->withTwoFactor()->create(['pseudonym' => 'moderatrice_x', 'email' => 'moderatrice@example.org']);
        $illegal = Proposal::factory()->create(['title' => 'Titre illégal secret']);
        $offTopic = Proposal::factory()->create(['title' => 'Titre hors sujet visible']);
        $service = app(ModerationService::class);
        $service->hide($moderator, $illegal, ReportMotive::Illegal);
        $service->hide($moderator, $offTopic, ReportMotive::OffTopic);

        $response = $this->get('/journal-de-moderation')->assertOk()
            ->assertSee('Titre hors sujet visible')
            ->assertSee('Hors sujet')
            ->assertSee('Contenu illégal')
            ->assertSee('contenu non reproduit')
            ->assertDontSee('Titre illégal secret')
            ->assertDontSee('moderatrice_x')
            ->assertDontSee('moderatrice@example.org')
            ->assertSee('Modération');

        $entry = $illegal->moderationEntries()->first();
        $this->get("/journal-de-moderation/{$entry->id}")->assertOk()->assertDontSee('Titre illégal secret')->assertSee('Masqué');
        $this->get('/journal-de-moderation?action=hide&type=proposal')->assertOk()->assertSee('Titre hors sujet visible');
        $this->get('/journal-de-moderation?action=keep')->assertOk()->assertDontSee('Titre hors sujet visible');
    }

    public function test_chaque_action_cree_une_entree_avec_le_role_et_jamais_le_pseudonyme(): void
    {
        $editorial = User::factory()->role(Role::Editorial)->withTwoFactor()->create();
        $proposal = Proposal::factory()->create();

        app(ModerationService::class)->requestRewrite($editorial, $proposal, ReportMotive::Disinformation);

        $entry = ModerationLogEntry::sole();
        $this->assertSame(ModerationAction::RequestRewrite, $entry->action);
        $this->assertSame(ActorRole::Editorial, $entry->actor_role);
        $this->assertSame($editorial->id, $entry->actor_id);
        $this->assertNull($entry->details);
        $this->assertStringNotContainsString($editorial->pseudonym, $entry->toJson());
    }
}
