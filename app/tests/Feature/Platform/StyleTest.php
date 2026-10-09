<?php

namespace Tests\Feature\Platform;

use Tests\TestCase;

/** F7 et F8 : feuille de style compilée sans ressource externe, mouvement réduit honoré. */
class StyleTest extends TestCase
{
    public function test_la_feuille_compilee_honore_le_mouvement_reduit_et_ne_charge_rien_d_externe(): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (! is_file($manifestPath)) {
            $this->markTestSkipped('Assets non compilés (npm run build).');
        }

        /** @var array<string, array{file: string}> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $css = '';

        foreach ($manifest as $entry) {
            if (str_ends_with($entry['file'], '.css')) {
                $css .= (string) file_get_contents(public_path('build/'.$entry['file']));
            }
        }

        $this->assertNotSame('', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringNotContainsString('@import url(', $css);
        $this->assertDoesNotMatchRegularExpression('/url\(["\']?https?:/', $css);
        $this->assertStringNotContainsString('@font-face', $css);
    }

    public function test_les_vues_ne_contiennent_aucun_style_en_ligne(): void
    {
        $offenders = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php') && preg_match('/\sstyle="/', (string) file_get_contents($file->getPathname())) === 1) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders, 'Styles en ligne interdits (CSP) : '.implode(', ', $offenders));
    }
}
