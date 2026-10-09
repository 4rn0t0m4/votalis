<?php

namespace Tests\Feature\Participation;

use App\Enums\VoteValue;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use App\Services\Rankings;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RankingsTest extends TestCase
{
    use RefreshDatabase;

    private Theme $theme;

    protected function setUp(): void
    {
        parent::setUp();
        config(['votalis.rankings.min_votes' => 4, 'votalis.rankings.cache_seconds' => 0]);
        $this->theme = Theme::factory()->create();
    }

    /**
     * @param  list<array{VoteValue, VoteValue}>  $votes
     */
    private function proposalWithVotes(string $title, array $votes, bool $afterArguments = false): Proposal
    {
        $proposal = Proposal::factory()->create(['theme_id' => $this->theme->id, 'title' => $title]);
        $service = app(VoteService::class);

        foreach ($votes as [$desirable, $necessary]) {
            $user = User::factory()->create(['created_at' => now()->subMonth()]);

            if ($afterArguments) {
                $service->cast($user, $proposal, VoteValue::No, VoteValue::No, null, false);
            }

            $service->cast($user, $proposal, $desirable, $necessary, null, $afterArguments);
        }

        return $proposal;
    }

    public function test_les_plus_debattues(): void
    {
        $quiet = $this->proposalWithVotes('Calme', [[VoteValue::Yes, VoteValue::Yes]]);
        $busy = $this->proposalWithVotes('Animée', [[VoteValue::Yes, VoteValue::Yes], [VoteValue::No, VoteValue::No]]);
        Argument::factory()->count(3)->create(['proposal_id' => $busy->id]);

        $ranked = app(Rankings::class)->forTheme($this->theme, 'debattues');

        $this->assertSame(['Animée', 'Calme'], $ranked->pluck('title')->all());
        $this->assertStringContainsString('3 arguments', (string) $ranked->first()?->getAttribute('metric'));
    }

    public function test_les_plus_clivantes_avec_seuil_de_votes(): void
    {
        $yes = VoteValue::Yes;
        $no = VoteValue::No;
        $consensual = $this->proposalWithVotes('Consensuelle', [[$yes, $yes], [$yes, $yes], [$yes, $yes], [$yes, $yes]]);
        $divisive = $this->proposalWithVotes('Clivante', [[$yes, $yes], [$yes, $yes], [$no, $no], [$no, $no]]);
        $tooFew = $this->proposalWithVotes('Trop peu', [[$yes, $yes], [$no, $no]]);

        $ranked = app(Rankings::class)->forTheme($this->theme, 'clivantes');

        $this->assertSame('Clivante', $ranked->first()?->title);
        $this->assertStringContainsString('50 % oui · 50 % non', (string) $ranked->first()?->getAttribute('metric'));
        $this->assertNotContains('Trop peu', $ranked->pluck('title')->all());
        $this->assertLessThan($ranked->search(fn ($p) => $p->title === 'Consensuelle') ?: 99, 0);
    }

    public function test_necessaires_mais_pas_souhaitees(): void
    {
        $yes = VoteValue::Yes;
        $no = VoteValue::No;
        $this->proposalWithVotes('Agréable', [[$yes, $no], [$yes, $no], [$yes, $no], [$yes, $no]]);
        $this->proposalWithVotes('Amère', [[$no, $yes], [$no, $yes], [$no, $yes], [$yes, $yes]]);

        $ranked = app(Rankings::class)->forTheme($this->theme, 'necessaires');

        $this->assertSame(['Amère'], $ranked->pluck('title')->all());
        $this->assertStringContainsString('100 % nécessaire · 25 % souhaitable', (string) $ranked->first()?->getAttribute('metric'));
    }

    public function test_soutien_en_hausse_apres_lecture_des_arguments(): void
    {
        $this->proposalWithVotes('Convaincante', [[VoteValue::Yes, VoteValue::Yes], [VoteValue::Yes, VoteValue::No]], afterArguments: true);
        $this->proposalWithVotes('Stable', [[VoteValue::No, VoteValue::No]], afterArguments: true);
        $this->proposalWithVotes('Sans révision', [[VoteValue::Yes, VoteValue::Yes]]);

        $ranked = app(Rankings::class)->forTheme($this->theme, 'progression');

        $this->assertSame(['Convaincante'], $ranked->pluck('title')->all());
        $this->assertStringContainsString('2 votes passés à oui', (string) $ranked->first()?->getAttribute('metric'));
    }

    public function test_les_onglets_sont_servis_sur_la_page_de_theme_et_aucun_n_est_un_tri_par_soutien(): void
    {
        $this->proposalWithVotes('Une mesure', [[VoteValue::Yes, VoteValue::Yes]]);

        foreach (Rankings::TABS as $tab) {
            $this->get("/themes/{$this->theme->slug}?classement={$tab}")->assertOk()->assertSee(Rankings::labels()[$tab]);
        }

        $this->get("/themes/{$this->theme->slug}?classement=arbitrages")->assertSee('Pas encore assez');
        $this->get("/themes/{$this->theme->slug}?classement=consensuelles")->assertSee('pas encore activé');
        $this->get("/themes/{$this->theme->slug}?classement=inconnu")->assertOk();

        $this->assertNotContains('soutien', array_map('mb_strtolower', array_keys(Rankings::labels())));
        $this->assertNotContains('populaires', array_map('mb_strtolower', array_keys(Rankings::labels())));
    }

    public function test_le_classement_en_cache_ne_serialise_aucun_modele(): void
    {
        // Magasin qui sérialise réellement (contrairement à `array`) : avec `cache.serializable_classes`
        // à false, un modèle mis en cache reviendrait en __PHP_Incomplete_Class.
        config(['cache.default' => 'file', 'votalis.rankings.cache_seconds' => 300]);
        Cache::store('file')->flush();
        $theme = Theme::factory()->create();
        $proposal = Proposal::factory()->create(['theme_id' => $theme->id, 'title' => 'Fiche classée']);
        Argument::factory()->count(2)->create(['proposal_id' => $proposal->id]);

        $first = app(Rankings::class)->forTheme($theme, 'debattues');
        $second = app(Rankings::class)->forTheme($theme, 'debattues');

        $this->assertSame(['Fiche classée'], $first->pluck('title')->all());
        $this->assertSame(['Fiche classée'], $second->pluck('title')->all());
        $this->assertInstanceOf(Proposal::class, $second->first());
        $this->assertNotNull($second->first()?->getAttribute('metric'));
        $this->assertNotNull($second->first()?->theme);
        Cache::store('file')->flush();
    }
}
