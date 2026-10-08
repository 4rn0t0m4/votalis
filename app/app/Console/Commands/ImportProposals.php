<?php

namespace App\Console\Commands;

use App\Services\ProposalImporter;
use Illuminate\Console\Command;

class ImportProposals extends Command
{
    protected $signature = 'proposals:import {path : Fichier CSV (UTF-8, séparateur ;)} {--dry-run : Valide sans écrire}';

    protected $description = 'Importe un jeu de propositions d\'amorçage (format : docs/import-amorcage.md)';

    public function handle(ProposalImporter $importer): int
    {
        $report = $importer->import((string) $this->argument('path'), (bool) $this->option('dry-run'));

        foreach ($report->errors as $error) {
            $this->error("Ligne {$error['line']}, champ {$error['field']} : {$error['message']}");
        }

        if ($report->hasErrors()) {
            $this->error(count($report->errors).' erreur(s) : aucune proposition n\'a été écrite.');

            return self::FAILURE;
        }

        $this->info($report->imported.' proposition(s) '.($report->written ? 'importée(s).' : 'valide(s), rien n\'a été écrit (simulation).'));

        return self::SUCCESS;
    }
}
