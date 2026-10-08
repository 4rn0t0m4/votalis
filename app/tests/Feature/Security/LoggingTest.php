<?php

namespace Tests\Feature\Security;

use App\Logging\ScrubPersonalData;
use Illuminate\Support\Facades\Log;
use Monolog\Formatter\JsonFormatter;
use Tests\TestCase;

class LoggingTest extends TestCase
{
    public function test_les_journaux_ne_contiennent_ni_email_ni_adresse_ip(): void
    {
        $path = storage_path('logs/test-scrub.log');
        @unlink($path);

        config(['logging.channels.scrub-test' => [
            'driver' => 'single',
            'path' => $path,
            'formatter' => JsonFormatter::class,
            'tap' => [ScrubPersonalData::class],
        ]]);

        Log::channel('scrub-test')->warning('Échec de connexion pour citoyenne@example.org depuis 203.0.113.42', [
            'email' => 'citoyenne@example.org',
            'ip' => '203.0.113.42',
            'request' => ['identifier' => 'citoyenne@example.org', 'password' => 'secret'],
            'message' => 'contact: autre@example.net, ipv6 2001:db8::1',
        ]);

        $content = (string) file_get_contents($path);
        @unlink($path);

        $this->assertStringNotContainsString('example.org', $content);
        $this->assertStringNotContainsString('example.net', $content);
        $this->assertStringNotContainsString('203.0.113.42', $content);
        $this->assertStringNotContainsString('2001:db8::1', $content);
        $this->assertStringNotContainsString('secret', $content);
        $this->assertStringContainsString('[masqué]', $content);
        $this->assertJson(trim($content));
    }

    public function test_le_canal_par_defaut_est_configure_avec_le_nettoyage(): void
    {
        $this->assertContains(ScrubPersonalData::class, config('logging.channels.stderr.tap'));
        $this->assertContains(ScrubPersonalData::class, config('logging.channels.single.tap'));
    }
}
