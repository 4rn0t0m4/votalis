<?php

namespace Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_pages_legales_sont_publiques_et_lisent_la_configuration(): void
    {
        config(['votalis.legal.controller_name' => 'Association Exemple', 'votalis.legal.contact_email' => 'contact@exemple.test']);

        $this->get('/confidentialite')->assertOk()->assertSee('Association Exemple')->assertSee('consentement explicite')->assertSee('participant supprimé')->assertSee('cnil.fr');
        $this->get('/mentions-legales')->assertOk()->assertSee('Association Exemple')->assertSee('AGPL');
        $this->get('/cookies')->assertOk()->assertSee('XSRF-TOKEN')->assertSee('aucune bannière');
        $this->get('/inscription')->assertOk()->assertSee('/confidentialite');
    }
}
