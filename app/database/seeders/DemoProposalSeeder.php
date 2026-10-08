<?php

namespace Database\Seeders;

use App\Services\ProposalImporter;
use Illuminate\Database\Seeder;

/** Développement uniquement : importe le jeu d'amorçage synthétique de test. */
class DemoProposalSeeder extends Seeder
{
    public function run(ProposalImporter $importer): void
    {
        $report = $importer->import(base_path('tests/Fixtures/amorcage-200.csv'));

        if ($report->hasErrors()) {
            $this->command->error('Import du jeu de démonstration en échec : '.json_encode($report->errors, JSON_UNESCAPED_UNICODE));
        }
    }
}
