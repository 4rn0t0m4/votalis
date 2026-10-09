<?php

namespace App\Console\Commands;

use App\Services\Consensus;
use Illuminate\Console\Command;

class ConsensusCompute extends Command
{
    protected $signature = 'consensus:compute
        {--force : Calculer même si le classement par consensus est désactivé (essai, démonstration)}
        {--export= : Écrire dans ce fichier la charge pseudonymisée envoyée au service (audit)}';

    protected $description = 'Calcule le classement par consensus (familles de votants) et enregistre les scores (lot 7, CDC section 5)';

    public function handle(Consensus $consensus): int
    {
        if (! $consensus->enabled() && ! $this->option('force')) {
            $this->warn('Classement par consensus désactivé (CONSENSUS_ENABLED=false) : aucun calcul. Utilisez --force pour un essai.');

            return self::SUCCESS;
        }

        $export = $this->option('export');
        $run = $consensus->run(is_string($export) && $export !== '' ? $export : null);

        $this->info(sprintf(
            'Calcul n° %d : %s ; %d participants retenus, %d propositions, %d groupes%s.',
            $run->id,
            $run->status->value,
            $run->participants,
            $run->proposals,
            $run->k,
            $run->input_digest ? ', empreinte '.substr($run->input_digest, 0, 12) : '',
        ));

        if ($run->error) {
            $this->error($run->error);
        }

        if (is_string($export) && $export !== '' && $run->input_digest) {
            $this->line("Charge écrite dans {$export} ; recalcul : python consensus/scripts/recalcul.py {$export}");
        }

        return self::SUCCESS;
    }
}
