<?php

namespace Tests\Feature\Account;

use App\Enums\ReportMotive;
use App\Enums\VoteValue;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ReportService;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_export_contient_mes_donnees_et_aucune_donnee_d_un_tiers(): void
    {
        $me = User::factory()->create(['pseudonym' => 'moi_meme', 'email' => 'moi@example.org', 'created_at' => now()->subMonth()]);
        $other = User::factory()->create(['pseudonym' => 'quelqu_un_d_autre', 'email' => 'autre@example.org', 'created_at' => now()->subMonth()]);
        $theirs = Proposal::factory()->create(['author_id' => $other->id, 'title' => 'Proposition de quelqu’un d’autre']);
        $mine = Proposal::factory()->create(['author_id' => $me->id, 'title' => 'Ma proposition à moi']);
        Argument::factory()->create(['author_id' => $me->id, 'proposal_id' => $theirs->id, 'body' => 'Mon argument personnel sur cette mesure.']);
        Argument::factory()->create(['author_id' => $other->id, 'proposal_id' => $mine->id, 'body' => 'Argument de l’autre participant.']);
        app(VoteService::class)->cast($me, $theirs, VoteValue::Yes, VoteValue::No, 'elle soit évaluée', false);
        app(VoteService::class)->cast($other, $mine, VoteValue::No, VoteValue::No, null, false);
        app(ReportService::class)->report($me, $theirs, ReportMotive::OffTopic, 'Précision privée.');

        $this->actingAs($me)->get('/mon-compte/donnees')->assertOk()->assertSee('Télécharger mes données');

        $response = $this->actingAs($me)->post('/mon-compte/donnees/export')->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertHeader('Cache-Control', 'no-store, private');
        $json = $response->getContent();
        $data = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('moi_meme', $data['compte']['pseudonyme']);
        $this->assertSame('moi@example.org', $data['compte']['email']);
        $this->assertCount(1, $data['votes']);
        $this->assertSame('elle soit évaluée', $data['votes'][0]['condition']);
        $this->assertSame('Ma proposition à moi', $data['propositions'][0]['title']);
        $this->assertSame('Mon argument personnel sur cette mesure.', $data['arguments'][0]['texte']);
        $this->assertSame('off_topic', $data['signalements_emis'][0]['motif']);

        foreach (['quelqu_un_d_autre', 'autre@example.org', 'Argument de l’autre participant'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, (string) $json);
        }
    }

    public function test_l_export_exige_d_etre_connecte(): void
    {
        $this->post('/mon-compte/donnees/export')->assertRedirect('/connexion');
    }
}
