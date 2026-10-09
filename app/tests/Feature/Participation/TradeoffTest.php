<?php

namespace Tests\Feature\Participation;

use App\Enums\Role;
use App\Enums\SuggestionStatus;
use App\Enums\TradeoffStatus;
use App\Livewire\TradeoffExercise;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\Tradeoff;
use App\Models\TradeoffItem;
use App\Models\TradeoffSuggestion;
use App\Models\User;
use App\Services\Rankings;
use App\Services\TradeoffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TradeoffTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create(['created_at' => now()->subMonth()]);
    }

    /**
     * @param  list<float>  $impacts
     * @return array{Tradeoff, Collection<int, TradeoffItem>}
     */
    private function tradeoffWithItems(array $impacts, ?Theme $theme = null): array
    {
        $tradeoff = Tradeoff::factory()->create(['constraint_value' => 40, 'theme_id' => $theme?->id]);
        $service = app(TradeoffService::class);
        $items = collect();

        foreach ($impacts as $i => $impact) {
            $proposal = Proposal::factory()->create(['title' => "Mesure {$i}"] + ($theme ? ['theme_id' => $theme->id] : []));
            $items->push($service->addItem($tradeoff, ['proposal_id' => $proposal->id, 'impact' => $impact, 'uncertainty' => '± 20 %', 'source_url' => 'https://www.exemple.gouv.fr/chiffrage']));
        }

        return [$tradeoff, $items];
    }

    public function test_la_contrainte_doit_etre_atteinte_et_seule_la_derniere_combinaison_compte(): void
    {
        [$tradeoff, $items] = $this->tradeoffWithItems([25, 20, 10]);
        $user = $this->participant();
        $service = app(TradeoffService::class);

        try {
            $service->answer($user, $tradeoff, [$items[2]->id]);
            $this->fail('La contrainte non atteinte aurait dû être refusée.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('contrainte', mb_strtolower(implode(' ', $e->errors()['items'])));
        }

        $service->answer($user, $tradeoff, [$items[0]->id, $items[1]->id], [$items[0]->id => 'qu’elle soit évaluée après deux ans']);
        $service->answer($user, $tradeoff, [$items[0]->id, $items[2]->id, $items[1]->id]);

        $this->assertDatabaseCount('tradeoff_answers', 1);
        $answer = $service->answerOf($user, $tradeoff);
        $this->assertSame(3, count($answer->item_ids));
        $this->assertSame('55.00', $answer->total);
        $this->assertCount(2, $service->historyOf($user, $tradeoff));
    }

    public function test_une_mesure_etrangere_ou_une_condition_sur_une_mesure_non_choisie_sont_refusees(): void
    {
        [$tradeoff, $items] = $this->tradeoffWithItems([45, 10]);
        [, $otherItems] = $this->tradeoffWithItems([45]);
        $service = app(TradeoffService::class);

        try {
            $service->answer($this->participant(), $tradeoff, [$otherItems[0]->id]);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }

        try {
            $service->answer($this->participant(), $tradeoff, [$items[0]->id], [$items[1]->id => 'si…']);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('conditions', $e->errors());
        }

        $this->assertDatabaseCount('tradeoff_answers', 0);
    }

    public function test_un_arbitrage_brouillon_ou_clos_ne_prend_pas_de_reponse(): void
    {
        foreach ([Tradeoff::factory()->draft(), Tradeoff::factory()->closed()] as $factory) {
            $tradeoff = $factory->create();
            $item = app(TradeoffService::class)->addItem($tradeoff, ['proposal_id' => Proposal::factory()->create()->id, 'impact' => 50, 'uncertainty' => 'x', 'source_url' => 'https://www.exemple.gouv.fr/a']);

            try {
                app(TradeoffService::class)->answer($this->participant(), $tradeoff, [$item->id]);
                $this->fail();
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('tradeoff', $e->errors());
            }
        }
    }

    public function test_une_mesure_sans_chiffrage_ou_sans_source_n_entre_pas_dans_un_arbitrage(): void
    {
        $tradeoff = Tradeoff::factory()->draft()->create();
        $proposal = Proposal::factory()->create();
        $service = app(TradeoffService::class);

        foreach ([
            ['impact' => '', 'uncertainty' => '± 10 %', 'source_url' => 'https://www.exemple.gouv.fr/a'],
            ['impact' => 10, 'uncertainty' => '', 'source_url' => 'https://www.exemple.gouv.fr/a'],
            ['impact' => 10, 'uncertainty' => '± 10 %', 'source_url' => ''],
            ['impact' => 10, 'uncertainty' => '± 10 %', 'source_url' => 'pas une url'],
        ] as $data) {
            try {
                $service->addItem($tradeoff, ['proposal_id' => $proposal->id] + $data);
                $this->fail('Ajout sans chiffrage fiable accepté : '.json_encode($data));
            } catch (ValidationException) {
            }
        }

        $this->assertDatabaseCount('tradeoff_items', 0);
    }

    public function test_un_arbitrage_s_ouvre_avec_au_moins_deux_mesures(): void
    {
        $tradeoff = Tradeoff::factory()->draft()->create();
        $service = app(TradeoffService::class);
        $service->addItem($tradeoff, ['proposal_id' => Proposal::factory()->create()->id, 'impact' => 50, 'uncertainty' => 'x', 'source_url' => 'https://www.exemple.gouv.fr/a']);

        $this->expectException(ValidationException::class);
        $service->changeStatus($tradeoff, TradeoffStatus::Open);
    }

    public function test_seul_le_comite_administre_les_arbitrages(): void
    {
        $this->actingAs(User::factory()->create())->get('/comite/arbitrages')->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Moderator)->withTwoFactor()->create())->get('/comite/arbitrages')->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Admin)->withTwoFactor()->create())->get('/comite/arbitrages')->assertForbidden();

        $editorial = User::factory()->role(Role::Editorial)->withTwoFactor()->create();
        $this->actingAs($editorial)->get('/comite/arbitrages')->assertOk();
        $this->actingAs($editorial)->post('/comite/arbitrages', [
            'title' => 'Financer 3 % du PIB pour la défense', 'objective' => 'Trouver 30 milliards par an pour atteindre 3 % du PIB.',
            'constraint_value' => 30, 'unit' => 'Md€', 'direction' => 'at_least',
        ])->assertRedirect();

        $tradeoff = Tradeoff::sole();
        $this->assertSame('financer-3-du-pib-pour-la-defense', $tradeoff->slug);
        $this->assertSame(TradeoffStatus::Draft, $tradeoff->status);

        auth()->logout();
        $this->get("/arbitrages/{$tradeoff->slug}")->assertForbidden();
        $this->actingAs(User::factory()->create())->get("/arbitrages/{$tradeoff->slug}")->assertForbidden();
    }

    public function test_l_exercice_livewire_calcule_la_jauge_et_enregistre(): void
    {
        [$tradeoff, $items] = $this->tradeoffWithItems([25, 20, 10]);
        $user = $this->participant();

        $component = Livewire::actingAs($user)->test(TradeoffExercise::class, ['tradeoff' => $tradeoff])
            ->assertSee('Contrainte non atteinte')
            ->call('toggle', $items[0]->id)
            ->assertSee('25 Md€')
            ->call('submit')
            ->assertHasErrors(['items'])
            ->call('toggle', $items[1]->id)
            ->assertSee('45 Md€')
            ->assertSee('Contrainte atteinte')
            ->set('conditions.'.$items[0]->id, 'qu’elle soit évaluée')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Votre combinaison est enregistrée');

        $this->assertDatabaseCount('tradeoff_answers', 1);
        $this->get("/arbitrages/{$tradeoff->slug}")->assertOk()->assertSee('Mesure 0');
        $this->get("/arbitrages/{$tradeoff->slug}/resultats")->assertOk()->assertSee('1 participant')->assertSee('100 %')->assertSee('qu’elle soit évaluée');
    }

    public function test_un_visiteur_ne_peut_pas_repondre(): void
    {
        [$tradeoff, $items] = $this->tradeoffWithItems([45]);

        Livewire::test(TradeoffExercise::class, ['tradeoff' => $tradeoff])
            ->assertSee('Connectez-vous')
            ->call('toggle', $items[0]->id)
            ->call('submit')
            ->assertForbidden();

        $this->assertDatabaseCount('tradeoff_answers', 0);
    }

    public function test_une_option_a_impact_negatif_abaisse_le_total_sans_casser_la_jauge(): void
    {
        [$tradeoff, $items] = $this->tradeoffWithItems([25, -10]);

        Livewire::actingAs($this->participant())->test(TradeoffExercise::class, ['tradeoff' => $tradeoff])
            ->call('toggle', $items[1]->id)
            ->assertSee('-10 Md€')
            ->assertSeeHtml('aria-valuenow="0"')
            ->call('toggle', $items[0]->id)
            ->assertSee('15 Md€')
            ->assertSeeHtml('aria-valuenow="38"')
            ->assertSee('Contrainte non atteinte');
    }

    public function test_les_resultats_agregent_frequences_combinaisons_et_conditions(): void
    {
        [$tradeoff, $items] = $this->tradeoffWithItems([25, 20, 10]);
        $service = app(TradeoffService::class);

        $service->answer($this->participant(), $tradeoff, [$items[0]->id, $items[1]->id], [$items[0]->id => 'Avec évaluation']);
        $service->answer($this->participant(), $tradeoff, [$items[0]->id, $items[1]->id], [$items[0]->id => 'avec évaluation']);
        $service->answer($this->participant(), $tradeoff, [$items[0]->id, $items[2]->id, $items[1]->id]);

        $results = $service->results($tradeoff);

        $this->assertSame(3, $results['participants']);
        $this->assertSame($items[0]->id, $results['items'][0]['item']->id);
        $this->assertSame(100, $results['items'][0]['percent']);
        $this->assertSame(['text' => 'Avec évaluation', 'count' => 2], $results['items'][0]['conditions'][0]);
        $this->assertSame(2, $results['combinations'][0]['count']);
        $this->assertSame(67, $results['combinations'][0]['percent']);
    }

    public function test_les_participants_suggerent_une_mesure_que_le_comite_ajoute_ou_ecarte(): void
    {
        $theme = Theme::factory()->create();
        [$tradeoff, $items] = $this->tradeoffWithItems([45], $theme);
        $candidate = Proposal::factory()->create(['theme_id' => $theme->id, 'title' => 'Mesure suggérée']);
        $user = $this->participant();

        Livewire::actingAs($user)->test(TradeoffExercise::class, ['tradeoff' => $tradeoff])
            ->set('suggestionProposalId', $candidate->id)
            ->set('suggestionNote', 'Chiffrée à 5 Md€ par la Cour des comptes')
            ->call('suggest')
            ->assertHasNoErrors()
            ->assertSee('le comité éditorial examinera');

        $this->assertDatabaseHas('tradeoff_suggestions', ['proposal_id' => $candidate->id, 'status' => 'pending']);

        $editorial = User::factory()->role(Role::Editorial)->withTwoFactor()->create();
        $this->actingAs($editorial)->get("/comite/arbitrages/{$tradeoff->slug}/modifier")->assertOk()->assertSee('Mesure suggérée');
        $this->actingAs($editorial)->post("/comite/arbitrages/{$tradeoff->slug}/mesures", [
            'proposal_id' => $candidate->id, 'impact' => 5, 'uncertainty' => '± 10 %', 'source_url' => 'https://www.ccomptes.fr/x',
        ])->assertRedirect();

        $this->assertSame(SuggestionStatus::Accepted, TradeoffSuggestion::sole()->status);
        $this->assertDatabaseCount('tradeoff_items', 2);
    }

    public function test_le_classement_des_plus_choisies_dans_les_arbitrages(): void
    {
        config(['votalis.rankings.cache_seconds' => 0]);
        $theme = Theme::factory()->create();
        [$tradeoff, $items] = $this->tradeoffWithItems([25, 20, 10], $theme);
        $service = app(TradeoffService::class);

        $service->answer($this->participant(), $tradeoff, [$items[0]->id, $items[1]->id]);
        $service->answer($this->participant(), $tradeoff, [$items[0]->id, $items[2]->id, $items[1]->id]);

        $ranked = app(Rankings::class)->forTheme($theme, 'arbitrages');

        $this->assertSame(['Mesure 0', 'Mesure 1', 'Mesure 2'], $ranked->pluck('title')->all());
        $this->assertStringContainsString('100 %', (string) $ranked->first()?->getAttribute('metric'));
        $this->assertStringContainsString('50 %', (string) $ranked->last()?->getAttribute('metric'));
    }

    public function test_la_page_des_arbitrages_liste_les_exercices_publics(): void
    {
        Tradeoff::factory()->create(['title' => 'Arbitrage ouvert']);
        Tradeoff::factory()->closed()->create(['title' => 'Arbitrage clos']);
        Tradeoff::factory()->draft()->create(['title' => 'Arbitrage brouillon']);

        $this->get('/arbitrages')->assertOk()->assertSeeInOrder(['Arbitrage ouvert', 'Arbitrage clos'])->assertDontSee('Arbitrage brouillon');
    }
}
