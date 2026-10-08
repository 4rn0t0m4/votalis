<?php

namespace App\Console\Commands;

use App\Services\IntegrityScanner;
use Illuminate\Console\Command;

class IntegrityScan extends Command
{
    protected $signature = 'integrity:scan';

    protected $description = 'Calcule les signaux d’intégrité des 24 dernières heures et les soumet aux modérateurs (aucune action automatique)';

    public function handle(IntegrityScanner $scanner): int
    {
        $signals = $scanner->scan();

        $this->info($signals->count().' signal(aux) enregistré(s).');

        return self::SUCCESS;
    }
}
