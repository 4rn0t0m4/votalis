<?php

namespace App\Support;

use RuntimeException;

/**
 * Haché HMAC-SHA256 de l'e-mail normalisé, avec une clé dédiée distincte d'APP_KEY.
 * Sert à l'unicité et à la connexion sans jamais stocker ni interroger l'e-mail en clair.
 */
final class EmailHasher
{
    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function hash(string $email): string
    {
        return hash_hmac('sha256', self::normalize($email), self::key());
    }

    private static function key(): string
    {
        $key = (string) config('votalis.email_hash_key');

        if ($key === '') {
            throw new RuntimeException('EMAIL_HASH_KEY manquante : exécutez php artisan votalis:hash-key.');
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            if ($decoded === false) {
                throw new RuntimeException('EMAIL_HASH_KEY invalide.');
            }

            return $decoded;
        }

        return $key;
    }
}
