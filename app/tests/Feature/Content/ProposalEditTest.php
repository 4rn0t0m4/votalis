<?php

namespace Tests\Feature\Content;

use App\Enums\RevisionKind;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use App\Services\ProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProposalEditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function inputFrom(Proposal $proposal, array $overrides = []): array
    {
        return array_merge([
            'theme_id' => $proposal->theme_id,
            'title' => $proposal->title,
            'problem' => $proposal->problem,
            'measure' => $proposal->measure,
            'cost_estimate' => $proposal->cost_estimate,
            'cost_unknown' => $proposal->cost_unknown,
            'sources' => $proposal->sources->where('is_personal', false)->pluck('url')->all(),
            'personal_source' => false,
        ], $overrides);
    }

    public function test_seul_l_auteur_peut_modifier(): void
    {
        $proposal = Proposal::factory()->create();

        $this->actingAs(User::factory()->create())->get("/propositions/{$proposal->id}/modifier")->assertForbidden();
        $this->actingAs($proposal->author)->get("/propositions/{$proposal->id}/modifier")->assertOk();
    }

    public function test_une_modification_libre_cree_une_revision_de_contenu(): void
    {
        $proposal = Proposal::factory()->create();

        $updated = app(ProposalService::class)->update($proposal, $this->inputFrom($proposal, ['measure' => 'Une mesure entièrement réécrite, avec un autre dispositif et un autre calendrier.']), $proposal->author);

        $this->assertSame('Une mesure entièrement réécrite, avec un autre dispositif et un autre calendrier.', $updated->measure);
        $this->assertSame(RevisionKind::Content, $updated->revisions->first()?->kind);
    }

    public function test_apres_le_premier_vote_seule_une_correction_de_forme_passe(): void
    {
        $proposal = Proposal::factory()->locked()->create(['title' => 'Plafoner les dépassements d’honoraires']);

        $updated = app(ProposalService::class)->update($proposal, $this->inputFrom($proposal, ['title' => 'Plafonner les dépassements d’honoraires']), $proposal->author);

        $this->assertSame('Plafonner les dépassements d’honoraires', $updated->title);
        $this->assertSame(RevisionKind::Typo, $updated->revisions->first()?->kind);
    }

    public function test_apres_le_premier_vote_un_changement_de_fond_est_refuse(): void
    {
        $proposal = Proposal::factory()->locked()->create();

        foreach ([
            ['measure' => 'Un dispositif complètement différent, qui change le sens de la proposition initiale.'],
            ['cost_estimate' => 'Un autre coût'],
            ['sources' => ['https://autre-source.example.org/x']],
            ['theme_id' => Theme::factory()->create()->id],
        ] as $change) {
            try {
                app(ProposalService::class)->update($proposal, $this->inputFrom($proposal, $change), $proposal->author);
                $this->fail('Le changement de fond aurait dû être refusé : '.json_encode(array_keys($change)));
            } catch (ValidationException $e) {
                $this->assertStringContainsString('variante', implode(' ', array_merge(...array_values($e->errors()))));
            }
        }

        $this->assertDatabaseCount('proposal_revisions', 0);
    }
}
