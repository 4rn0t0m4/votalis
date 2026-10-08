<?php

namespace App\Console\Commands;

use App\Services\PublicPageCache;
use App\Services\ReadOnlyMode;
use Illuminate\Console\Command;

class ReadOnlyCommand extends Command
{
    protected $signature = 'votalis:read-only {state : on, off ou status} {--message= : Message affiché au public}';

    protected $description = 'Active ou désactive le mode lecture seule (toute écriture refusée, lecture et connexion permises)';

    public function handle(ReadOnlyMode $readOnly, PublicPageCache $cache): int
    {
        $state = (string) $this->argument('state');

        match ($state) {
            'on' => $readOnly->enable(is_string($this->option('message')) ? $this->option('message') : null),
            'off' => $readOnly->disable(),
            'status' => null,
            default => throw new \InvalidArgumentException('État attendu : on, off ou status.'),
        };

        if ($state !== 'status') {
            $cache->flush();
        }

        $this->info($readOnly->enabled() ? 'Lecture seule : ACTIVÉE — '.$readOnly->message() : 'Lecture seule : désactivée.');

        return self::SUCCESS;
    }
}
