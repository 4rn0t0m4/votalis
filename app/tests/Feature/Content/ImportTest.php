<?php

namespace Tests\Feature\Content;

use App\Enums\ProposalOrigin;
use App\Models\Proposal;
use App\Models\ProposalSource;
use Database\Seeders\ThemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ThemeSeeder::class);
    }

    public function test_200_propositions_d_amorcage_s_importent_sans_erreur(): void
    {
        $this->artisan('proposals:import', ['path' => base_path('tests/Fixtures/amorcage-200.csv')])
            ->expectsOutputToContain('200 proposition(s) importée(s)')
            ->assertSuccessful();

        $this->assertDatabaseCount('proposals', 200);
        $this->assertDatabaseCount('proposal_revisions', 200);
        $this->assertSame(200, Proposal::query()->where('origin', ProposalOrigin::Seed)->whereNull('author_id')->whereNotNull('seed_source')->count());
        $this->assertGreaterThan(200, ProposalSource::count());
        $this->assertSame(40, Proposal::query()->whereHas('theme', fn ($q) => $q->where('slug', 'sante'))->count());
    }

    public function test_une_ligne_invalide_annule_tout_et_nomme_la_ligne(): void
    {
        $this->artisan('proposals:import', ['path' => base_path('tests/Fixtures/amorcage-invalide.csv')])
            ->expectsOutputToContain('Ligne 3, champ title')
            ->expectsOutputToContain('Ligne 4, champ theme_slug')
            ->assertFailed();

        $this->assertDatabaseCount('proposals', 0);
    }

    public function test_la_simulation_n_ecrit_rien(): void
    {
        $this->artisan('proposals:import', ['path' => base_path('tests/Fixtures/amorcage-200.csv'), '--dry-run' => true])
            ->expectsOutputToContain('200 proposition(s) valide(s)')
            ->assertSuccessful();

        $this->assertDatabaseCount('proposals', 0);
    }

    public function test_un_en_tete_incorrect_est_refuse(): void
    {
        $path = storage_path('app/test-mauvais-en-tete.csv');
        file_put_contents($path, "titre;probleme\nA;B\n");

        $this->artisan('proposals:import', ['path' => $path])->expectsOutputToContain('Colonnes attendues')->assertFailed();
        @unlink($path);
    }
}
