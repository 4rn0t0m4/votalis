<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;

/** Refuse les domaines d'e-mails jetables (liste versionnée dans resources/data). */
class NotDisposableEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $at = strrpos($value, '@');

        if ($at === false) {
            return;
        }

        $domain = mb_strtolower(trim(substr($value, $at + 1)));

        if (self::isDisposable($domain)) {
            $fail(__('Les adresses e-mail jetables ne sont pas acceptées.'));
        }
    }

    public static function isDisposable(string $domain): bool
    {
        return isset(self::domains()[mb_strtolower(trim($domain))]);
    }

    /**
     * @return array<string, true>
     */
    private static function domains(): array
    {
        /** @var array<string, true> */
        return Cache::remember('disposable-domains', now()->addDay(), function (): array {
            $path = (string) config('votalis.disposable_domains_path');
            $lines = is_file($path) ? (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : [];

            return array_fill_keys(array_map(fn (string $l) => mb_strtolower(trim($l)), $lines), true);
        });
    }
}
