<?php

namespace App\Console\Commands;

use App\Services\TransparencyReporter;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TransparencyReportCommand extends Command
{
    protected $signature = 'transparency:report {--from= : Début de période (AAAA-MM-JJ)} {--to= : Fin de période (AAAA-MM-JJ)}';

    protected $description = 'Génère le rapport de transparence du trimestre précédent, ou d’une période donnée';

    public function handle(TransparencyReporter $reporter): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        $report = is_string($from) && is_string($to)
            ? $reporter->generate(Carbon::parse($from), Carbon::parse($to))
            : $reporter->previousQuarter();

        $this->info('Rapport généré : '.$report->title());

        return self::SUCCESS;
    }
}
