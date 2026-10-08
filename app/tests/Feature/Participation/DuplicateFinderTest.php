<?php

namespace Tests\Feature\Participation;

use App\Enums\ProposalStatus;
use App\Livewire\ProposalForm;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use App\Services\DuplicateFinder;
use App\Services\EmbeddingClient;
use App\Services\ProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DuplicateFinderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Vecteurs de test à 384 dimensions : la direction est portée par les premières composantes.
     *
     * @return list<float>
     */
    private function vector(float $x, float $y): array
    {
        $v = array_fill(0, 384, 0.0);
        $norm = sqrt($x * $x + $y * $y);
        $v[0] = $x / $norm;
        $v[1] = $y / $norm;

        return $v;
    }

    private function storeEmbedding(Proposal $proposal, array $vector): void
    {
        Proposal::query()->whereKey($proposal->id)->update(['embedding' => EmbeddingClient::literal($vector), 'embedded_at' => now()]);
    }

    public function test_retourne_les_fiches_proches_au_dessus_du_seuil_sans_les_fiches_exclues_ou_masquees(): void
    {
        $close = Proposal::factory()->create(['title' => 'Alléger les cotisations des petites entreprises']);
        $far = Proposal::factory()->create(['title' => 'Créer mille lits d’hôpital']);
        $hidden = Proposal::factory()->create(['status' => ProposalStatus::Hidden]);
        $self = Proposal::factory()->create();

        $this->storeEmbedding($close, $this->vector(1.0, 0.1));
        $this->storeEmbedding($far, $this->vector(0.0, 1.0));
        $this->storeEmbedding($hidden, $this->vector(1.0, 0.0));
        $this->storeEmbedding($self, $this->vector(1.0, 0.0));

        $results = app(DuplicateFinder::class)->similarToVector($this->vector(1.0, 0.0), [$self->id]);

        $this->assertSame([$close->id], $results->pluck('id')->all());
        $this->assertGreaterThan(0.99, (float) $results->first()?->getAttribute('similarity'));
    }

    public function test_le_formulaire_suggere_les_doublons_pendant_la_redaction(): void
    {
        $existing = Proposal::factory()->create(['title' => 'Alléger les cotisations des petites entreprises']);
        $this->storeEmbedding($existing, $this->vector(1.0, 0.0));

        Http::fake(['localhost:8001/embed' => Http::response(['model' => 'x', 'dimension' => 384, 'vectors' => [$this->vector(1.0, 0.05)]])]);

        $theme = Theme::factory()->create();

        Livewire::actingAs(User::factory()->create())->test(ProposalForm::class)
            ->set('theme_id', $theme->id)
            ->set('title', 'Baisser les charges des PME')
            ->assertSet('similarChecked', false)
            ->set('measure', 'Réduire les cotisations patronales des entreprises de moins de 50 salariés.')
            ->assertSet('similarChecked', true)
            ->assertSee('Des propositions proches existent déjà')
            ->assertSee('Alléger les cotisations des petites entreprises')
            ->assertSee('Soutenir');

        Http::assertSent(fn ($request) => str_contains((string) $request['texts'][0], 'Baisser les charges des PME'));
    }

    public function test_sans_service_le_formulaire_reste_utilisable(): void
    {
        Http::fake(['localhost:8001/*' => Http::response('', 503)]);

        Livewire::actingAs(User::factory()->create())->test(ProposalForm::class)
            ->set('theme_id', Theme::factory()->create()->id)
            ->set('title', 'Baisser les charges des PME')
            ->set('measure', 'Réduire les cotisations patronales des entreprises de moins de 50 salariés.')
            ->assertSet('similar', [])
            ->assertSee('Aucune proposition proche trouvée');
    }

    public function test_le_depot_met_en_file_le_calcul_du_vecteur(): void
    {
        Http::fake(['localhost:8001/embed' => Http::response(['model' => 'x', 'dimension' => 384, 'vectors' => [$this->vector(0.3, 0.7)]])]);

        $proposal = app(ProposalService::class)->create([
            'theme_id' => Theme::factory()->create()->id,
            'title' => 'Plafonner les dépassements d’honoraires',
            'problem' => 'Les dépassements creusent les inégalités.',
            'measure' => 'Plafonner à 50 % du tarif conventionnel.',
            'cost_estimate' => 'Neutre', 'cost_unknown' => false,
            'sources' => ['https://www.ccomptes.fr/rapport'], 'personal_source' => false,
        ], User::factory()->create(['created_at' => now()->subMonth()]));

        $proposal->refresh();
        $this->assertNotNull($proposal->embedded_at);
        $this->assertSame(config('votalis.embeddings.version'), $proposal->embedding_version);
    }
}
