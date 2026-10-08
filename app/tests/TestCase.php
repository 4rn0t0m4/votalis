<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /** Corps de la réponse HIBP simulée (vide = aucune fuite connue). */
    private string $hibpBody = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Vérification des mots de passe fuités (HIBP, k-anonymat) : aucune fuite par défaut.
        Http::fake(['api.pwnedpasswords.com/*' => fn () => Http::response($this->hibpBody, 200)]);
    }

    /** Simule une réponse HIBP où le mot de passe donné figure dans une fuite. */
    protected function fakeLeakedPassword(string $password): void
    {
        $suffix = strtoupper(substr(sha1($password), 5));

        $this->hibpBody = "{$suffix}:1234\r\nABCDEF0123456789ABCDEF0123456789ABCDEF0:1";
    }

    /**
     * @return array<string, string>
     */
    protected function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'pseudonym' => 'Citoyenne_42',
            'email' => 'citoyenne@example.org',
            'password' => 'une-phrase-longue-et-sure',
            'password_confirmation' => 'une-phrase-longue-et-sure',
            'consent' => '1',
        ], $overrides);
    }
}
