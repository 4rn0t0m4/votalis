<?php

namespace Tests\Feature\Content;

use App\Models\Proposal;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CDC section 12 : poids de page initial sous 300 Ko sur les pages publiques. */
class PageWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_pages_publiques_du_lot_pesent_moins_de_300_ko(): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (! is_file($manifestPath)) {
            $this->markTestSkipped('Assets non compilés (npm run build).');
        }

        $theme = Theme::factory()->create();
        Proposal::factory()->count(20)->create(['theme_id' => $theme->id]);
        $proposal = Proposal::factory()->create(['theme_id' => $theme->id]);

        /** @var array<string, array{file: string}> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $assets = array_sum(array_map(fn (array $entry) => filesize(public_path('build/'.$entry['file'])) ?: 0, $manifest));

        foreach (['/themes', "/themes/{$theme->slug}", $proposal->url()] as $uri) {
            $html = strlen((string) $this->get($uri)->assertOk()->getContent());
            $this->assertLessThan(300 * 1024, $html + $assets, "Page {$uri} : HTML {$html} octets + assets {$assets} octets");
        }
    }
}
