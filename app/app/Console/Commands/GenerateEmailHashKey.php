<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateEmailHashKey extends Command
{
    protected $signature = 'votalis:hash-key {--show : Affiche la clé sans modifier .env}';

    protected $description = 'Génère la clé HMAC dédiée au haché des e-mails (EMAIL_HASH_KEY)';

    public function handle(): int
    {
        $key = 'base64:'.base64_encode(random_bytes(32));

        if ($this->option('show')) {
            $this->line($key);

            return self::SUCCESS;
        }

        $path = $this->laravel->environmentFilePath();
        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->error("Impossible de lire {$path}.");

            return self::FAILURE;
        }

        if (preg_match('/^EMAIL_HASH_KEY=.+$/m', $contents)) {
            $this->error('EMAIL_HASH_KEY est déjà définie. La changer rendrait tous les comptes introuvables.');

            return self::FAILURE;
        }

        $contents = preg_match('/^EMAIL_HASH_KEY=$/m', $contents)
            ? (string) preg_replace('/^EMAIL_HASH_KEY=$/m', "EMAIL_HASH_KEY={$key}", $contents)
            : $contents.PHP_EOL."EMAIL_HASH_KEY={$key}".PHP_EOL;

        file_put_contents($path, $contents);
        $this->info('EMAIL_HASH_KEY générée.');

        return self::SUCCESS;
    }
}
