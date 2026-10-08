<?php

namespace Tests\Feature\Content;

use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_liste_des_themes_affiche_la_hierarchie_et_les_compteurs(): void
    {
        $sante = Theme::factory()->create(['name' => 'Santé', 'position' => 1]);
        $hopital = Theme::factory()->childOf($sante)->create(['name' => 'Hôpital']);
        Proposal::factory()->count(2)->create(['theme_id' => $hopital->id]);
        Theme::factory()->closed()->create(['name' => 'Logement', 'position' => 2]);

        $this->get('/themes')->assertOk()->assertSeeInOrder(['Santé', 'Hôpital (2)', 'Logement', 'fermé aux nouvelles propositions']);
    }

    public function test_la_page_d_un_theme_liste_les_propositions_du_theme_et_de_ses_sous_themes(): void
    {
        $sante = Theme::factory()->create(['name' => 'Santé']);
        $hopital = Theme::factory()->childOf($sante)->create(['name' => 'Hôpital']);
        $p1 = Proposal::factory()->create(['theme_id' => $sante->id, 'title' => 'Instaurer un tarif unique']);
        $p2 = Proposal::factory()->create(['theme_id' => $hopital->id, 'title' => 'Créer mille lits']);
        Proposal::factory()->create(['title' => 'Hors thème']);

        $this->get("/themes/{$sante->slug}")->assertOk()
            ->assertSee($p1->title)->assertSee($p2->title)->assertDontSee('Hors thème');
    }

    public function test_un_theme_ferme_n_affiche_pas_le_bouton_de_depot(): void
    {
        $theme = Theme::factory()->closed()->create();

        $this->actingAs(User::factory()->create())->get("/themes/{$theme->slug}")
            ->assertOk()->assertDontSee('Proposer une mesure')->assertSee('fermé aux nouvelles propositions');
    }
}
