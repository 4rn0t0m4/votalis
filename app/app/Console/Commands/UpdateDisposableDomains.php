<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UpdateDisposableDomains extends Command
{
    protected $signature = 'votalis:update-disposable-domains';

    protected $description = 'Met à jour la liste des domaines d\'e-mails jetables depuis sa source publique';

    public function handle(): int
    {
        $response = Http::timeout(30)->get((string) config('votalis.disposable_domains_source'));

        if (! $response->successful()) {
            $this->error('Téléchargement impossible ('.$response->status().').');

            return self::FAILURE;
        }

        $domains = array_values(array_filter(array_map('trim', explode("\n", $response->body())), fn (string $d) => $d !== '' && ! str_starts_with($d, '#')));

        if (count($domains) < 1000) {
            $this->error('Liste suspecte ('.count($domains).' entrées) : fichier conservé.');

            return self::FAILURE;
        }

        file_put_contents((string) config('votalis.disposable_domains_path'), implode("\n", $domains)."\n");
        Cache::forget('disposable-domains');

        $this->info(count($domains).' domaines enregistrés.');

        return self::SUCCESS;
    }
}
