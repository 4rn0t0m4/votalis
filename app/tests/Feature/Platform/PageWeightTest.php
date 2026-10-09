<?php

namespace Tests\Feature\Platform;

use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sobriété (CDC section 11) : poids initial d'une page publique, HTML plus feuilles de style,
 * scripts et polices référencés, sous 300 Ko. Les ressources compilées sont lues sur le disque
 * (`public/build`) ; le script Livewire (`/livewire-<empreinte>/livewire.js`) est mesuré par requête.
 */
class PageWeightTest extends TestCase
{
    use RefreshDatabase;

    private const LIMIT = 300 * 1024;

    public function test_les_pages_principales_pesent_moins_de_300_ko_au_premier_chargement(): void
    {
        if (! is_dir(public_path('build/assets'))) {
            $this->markTestSkipped('Ressources Vite non compilées : lancer `npm run build`.');
        }

        $theme = Theme::factory()->create();
        $proposal = Proposal::factory()->create(['theme_id' => $theme->id]);
        $user = User::factory()->create();

        $pages = [
            '/' => null,
            route('themes.show', $theme, false) => null,
            $proposal->url() => null,
            '/inscription' => null,
            '/vote-rapide' => $user,
        ];

        foreach ($pages as $url => $as) {
            $response = $as === null ? $this->get($url) : $this->actingAs($as)->get($url);
            $response->assertOk();
            $html = (string) $response->getContent();
            $weight = strlen($html);

            preg_match_all('/(?:href|src)="([^"]+)"/', $html, $matches);
            $seen = [];
            foreach (array_unique($matches[1]) as $ref) {
                $path = (string) parse_url($ref, PHP_URL_PATH);
                if ($path === '' || isset($seen[$path])) {
                    continue;
                }
                $seen[$path] = true;

                if (preg_match('#^/build/.+\.(css|js|woff2?)$#', $path) === 1 && is_file(public_path($path))) {
                    $weight += (int) filesize(public_path($path));
                } elseif (preg_match('#^/livewire(?:-[0-9a-f]+)?/.+\.js$#', $path) === 1) {
                    $weight += strlen((string) $this->get($path)->getContent());
                }
            }

            $this->assertLessThan(self::LIMIT, $weight, sprintf('%s pèse %d Ko au premier chargement.', $url, intdiv($weight, 1024)));
        }
    }
}
