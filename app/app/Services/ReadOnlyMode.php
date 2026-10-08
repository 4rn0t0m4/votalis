<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mode lecture seule (CDC section 7, résilience) : activable en un clic par l'administrateur
 * technique ou par `votalis:read-only`. Toute écriture est refusée côté serveur ; la lecture,
 * la connexion et l'administration restent possibles.
 */
class ReadOnlyMode
{
    public const DEFAULT_MESSAGE = 'La plateforme est temporairement en lecture seule : vous pouvez tout consulter, mais les votes, propositions, arguments et signalements sont suspendus. Merci de revenir un peu plus tard.';

    private const CACHE_KEY = 'settings.read_only';

    public function enabled(): bool
    {
        /** @var array{enabled: bool, message: string|null} $state */
        $state = Cache::remember(self::CACHE_KEY, 10, fn () => $this->load());

        return $state['enabled'];
    }

    public function message(): string
    {
        /** @var array{enabled: bool, message: string|null} $state */
        $state = Cache::remember(self::CACHE_KEY, 10, fn () => $this->load());

        return $state['message'] ?: self::DEFAULT_MESSAGE;
    }

    public function enable(?string $message = null): void
    {
        $this->store('1', $message);
    }

    public function disable(): void
    {
        $this->store('0', null);
    }

    /**
     * @throws ValidationException
     */
    public function assertWritable(): void
    {
        if ($this->enabled()) {
            throw ValidationException::withMessages(['read_only' => [$this->message()]]);
        }
    }

    /**
     * @return array{enabled: bool, message: string|null}
     */
    private function load(): array
    {
        $rows = DB::table('settings')->whereIn('key', ['read_only', 'read_only_message'])->pluck('value', 'key');

        return [
            'enabled' => ($rows['read_only'] ?? '0') === '1',
            'message' => $rows['read_only_message'] ?? null,
        ];
    }

    private function store(string $enabled, ?string $message): void
    {
        DB::table('settings')->upsert([
            ['key' => 'read_only', 'value' => $enabled, 'updated_at' => now()],
            ['key' => 'read_only_message', 'value' => $message !== null ? mb_substr(trim($message), 0, 500) : null, 'updated_at' => now()],
        ], ['key'], ['value', 'updated_at']);

        Cache::forget(self::CACHE_KEY);
    }
}
