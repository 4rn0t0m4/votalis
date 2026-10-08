<?php

namespace Tests\Feature\Participation;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_recherche_trouve_une_fiche_par_son_texte_et_ignore_les_masquees(): void
    {
        $theme = Theme::factory()->create(['name' => 'Santé']);
        Proposal::factory()->create(['theme_id' => $theme->id, 'title' => 'Plafonner les dépassements d’honoraires']);
        Proposal::factory()->create(['theme_id' => $theme->id, 'title' => 'Créer mille lits', 'status' => ProposalStatus::Hidden]);
        Proposal::factory()->create(['title' => 'Instaurer un service civique']);

        $this->get('/recherche?q=honoraires')->assertOk()->assertSee('Plafonner les dépassements')->assertDontSee('service civique')->assertSee('1 résultat');
        $this->get('/recherche?q=lits')->assertOk()->assertSee('0 résultat');
        $this->get('/recherche?q=a')->assertOk()->assertDontSee('résultat');
    }

    public function test_l_index_ne_contient_rien_sur_l_auteur(): void
    {
        $proposal = Proposal::factory()->create();

        $this->assertSame(['id', 'title', 'problem', 'measure', 'theme', 'theme_id', 'subtheme_id', 'created_at'], array_keys($proposal->toSearchableArray()));
    }
}
