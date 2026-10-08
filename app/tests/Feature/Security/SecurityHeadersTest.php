<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_les_en_tetes_de_securite_sont_presents(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

        $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));
    }

    public function test_la_csp_est_stricte_avec_nonce_et_sans_unsafe(): void
    {
        $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9+\/=_-]{16,}'/", $csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
    }

    public function test_le_nonce_change_a_chaque_requete_et_figure_dans_la_page(): void
    {
        $first = $this->get('/');
        $second = $this->get('/');

        preg_match("/nonce-([^']+)'/", (string) $first->headers->get('Content-Security-Policy'), $m1);
        preg_match("/nonce-([^']+)'/", (string) $second->headers->get('Content-Security-Policy'), $m2);

        $this->assertNotSame($m1[1], $m2[1]);
        $this->assertStringContainsString('nonce="'.$m1[1].'"', $first->getContent());
    }

    public function test_hsts_est_envoye_en_https(): void
    {
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
    }

    public function test_aucune_ressource_n_est_chargee_depuis_un_domaine_tiers(): void
    {
        foreach (['/', '/connexion', '/inscription', '/comment-ca-marche'] as $uri) {
            $html = $this->get($uri)->getContent();

            $this->assertDoesNotMatchRegularExpression('/\s(src|href)=["\']https?:\/\/(?!localhost)/i', preg_replace('/<a\s[^>]*>/i', '', $html), "Ressource externe sur {$uri}");
        }
    }

    public function test_les_cookies_de_session_sont_securises(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertTrue(config('session.encrypt'));
    }
}
