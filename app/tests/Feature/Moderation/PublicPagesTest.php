<?php

namespace Tests\Feature\Moderation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_charte_liste_les_sept_motifs_et_la_procedure(): void
    {
        $this->get('/charte-de-moderation')->assertOk()
            ->assertSeeInOrder(['Contenu illégal', 'Attaque personnelle', 'Hors sujet', 'Doublon', 'Spam', 'Désinformation manifeste', 'Campagne coordonnée'])
            ->assertSee('Contester')
            ->assertSee((string) config('votalis.moderation.appeal_days'));
    }

    public function test_la_page_du_classement_explique_chaque_onglet_et_renvoie_vers_le_code(): void
    {
        $this->get('/comment-fonctionne-le-classement')->assertOk()
            ->assertSee('Les plus clivantes')
            ->assertSee('Nécessaires mais pas souhaitées')
            ->assertSee('Les plus choisies dans les arbitrages')
            ->assertSee('app/Services/Rankings.php')
            ->assertSee('docs/classement.md');
    }

    public function test_le_pied_de_page_et_comment_ca_marche_renvoient_vers_les_pages_de_transparence(): void
    {
        $this->get('/comment-ca-marche')->assertOk()
            ->assertSee('/charte-de-moderation')
            ->assertSee('/journal-de-moderation')
            ->assertSee('/comment-fonctionne-le-classement');
    }
}
