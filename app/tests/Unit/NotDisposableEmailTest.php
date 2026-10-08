<?php

namespace Tests\Unit;

use App\Rules\NotDisposableEmail;
use Tests\TestCase;

class NotDisposableEmailTest extends TestCase
{
    public function test_la_liste_versionnee_est_chargee(): void
    {
        $this->assertTrue(NotDisposableEmail::isDisposable('mailinator.com'));
        $this->assertTrue(NotDisposableEmail::isDisposable('MAILINATOR.COM'));
        $this->assertFalse(NotDisposableEmail::isDisposable('laposte.net'));
        $this->assertFalse(NotDisposableEmail::isDisposable('protonmail.com'));
    }
}
