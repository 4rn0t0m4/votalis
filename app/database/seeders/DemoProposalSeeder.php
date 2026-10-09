<?php

namespace Database\Seeders;

use App\Services\ProposalImporter;
use Illuminate\Database\Seeder;

/**
 * Développement uniquement : importe le jeu d'amorçage de démonstration (mesures réelles tirées de
 * rapports publics, voir docs/import-amorcage.md). Le jeu synthétique de tests reste dans tests/Fixtures.
 */
class DemoProposalSeeder extends Seeder
{
    public function run(ProposalImporter $importer): void
    {
        $report = $importer->import(database_path('data/amorcage-demo.csv'));

        if ($report->hasErrors()) {
            $this->command->error('Import du jeu de démonstration en échec : '.json_encode($report->errors, JSON_UNESCAPED_UNICODE));
        }
    }
}
