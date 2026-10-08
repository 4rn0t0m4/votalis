<?php

namespace Tests\Feature\Moderation;

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Enums\SignalStatus;
use App\Enums\SignalType;
use App\Enums\VoteValue;
use App\Models\IntegritySignal;
use App\Models\Proposal;
use App\Models\User;
use App\Services\EmbeddingClient;
use App\Services\IntegrityScanner;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IntegrityScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Chaque test fixe explicitement les seuils qu'il exerce ; les autres sont désactivés.
        config(['votalis.integrity' => array_merge(config('votalis.integrity'), [
            'registration_spike' => null, 'vote_spike' => null, 'identical_voting_min_shared' => null,
            'duplicate_content_similarity' => null, 'night_share' => null,
        ])]);
    }

    private function seedActivity(): void
    {
        $proposal = Proposal::factory()->create();
        foreach (range(1, 4) as $i) {
            $user = User::factory()->create();
            app(VoteService::class)->cast($user, $proposal, VoteValue::Yes, VoteValue::Yes, null, false);
        }
    }

    public function test_sans_seuil_configure_aucun_signal_n_est_calcule(): void
    {
        config(['votalis.integrity' => array_merge(config('votalis.integrity'), [
            'registration_spike' => null, 'vote_spike' => null, 'identical_voting_min_shared' => null,
            'duplicate_content_similarity' => null, 'night_share' => null,
        ])]);
        $this->seedActivity();

        $this->artisan('integrity:scan')->assertSuccessful();

        $this->assertDatabaseCount('integrity_signals', 0);
    }

    public function test_les_pics_d_inscriptions_et_de_votes_sont_signales_sans_aucune_action(): void
    {
        config(['votalis.integrity.registration_spike' => 3, 'votalis.integrity.vote_spike' => 3]);
        $this->seedActivity();

        $signals = app(IntegrityScanner::class)->scan();

        $this->assertCount(2, $signals);
        $spike = IntegritySignal::query()->where('type', SignalType::VoteSpike->value)->sole();
        $this->assertSame(['proposal_id' => Proposal::sole()->id], $spike->targets);
        $this->assertSame(4, $spike->details['count']);
        $this->assertSame(SignalStatus::New, $spike->status);
        $this->assertSame(1, IntegritySignal::query()->where('type', SignalType::RegistrationSpike->value)->count());

        // Rien n'est appliqué : contenus et comptes intacts, aucune entrée au journal.
        $this->assertSame(ProposalStatus::Published, Proposal::sole()->status);
        $this->assertSame(0, User::query()->whereNotNull('suspended_at')->count());
        $this->assertDatabaseCount('moderation_log', 0);

        // Un second passage le même jour ne duplique pas les signaux.
        app(IntegrityScanner::class)->scan();
        $this->assertDatabaseCount('integrity_signals', 2);
    }

    public function test_les_comptes_recents_votant_de_maniere_identique_sont_regroupes(): void
    {
        config(['votalis.integrity.identical_voting_min_shared' => 2, 'votalis.integrity.identical_voting_min_accounts' => 3]);
        $proposals = Proposal::factory()->count(3)->create();
        $clones = User::factory()->count(3)->create();
        $independent = User::factory()->create();
        $old = User::factory()->create(['created_at' => now()->subYear()]);
        $service = app(VoteService::class);

        foreach ($proposals as $proposal) {
            foreach ($clones as $clone) {
                $service->cast($clone, $proposal, VoteValue::Yes, VoteValue::No, null, false);
            }
            $service->cast($old, $proposal, VoteValue::Yes, VoteValue::No, null, false);
            $service->cast($independent, $proposal, VoteValue::No, VoteValue::Yes, null, false);
        }

        app(IntegrityScanner::class)->scan();

        $signal = IntegritySignal::query()->where('type', SignalType::IdenticalVoting->value)->sole();
        $this->assertSame($clones->pluck('id')->sort()->values()->all(), $signal->targets['user_ids']);
        $this->assertSame(3, $signal->details['accounts']);
    }

    public function test_les_propositions_presque_identiques_de_comptes_differents_sont_signalees(): void
    {
        config(['votalis.integrity.duplicate_content_similarity' => 0.95]);
        $a = Proposal::factory()->create();
        $b = Proposal::factory()->create();
        $c = Proposal::factory()->create();
        $same = Proposal::factory()->create(['author_id' => $a->author_id]);
        $vector = array_fill(0, 384, 0.0);
        $vector[0] = 1.0;
        $other = array_fill(0, 384, 0.0);
        $other[1] = 1.0;
        DB::table('proposals')->whereIn('id', [$a->id, $b->id, $same->id])->update(['embedding' => EmbeddingClient::literal($vector)]);
        DB::table('proposals')->where('id', $c->id)->update(['embedding' => EmbeddingClient::literal($other)]);

        app(IntegrityScanner::class)->scan();

        // a–b et b–same (auteurs différents, vecteurs identiques) ; a–same (même auteur) non ; c (vecteur différent) non.
        $pairs = IntegritySignal::query()->where('type', SignalType::NearDuplicateContent->value)->get()->map(fn (IntegritySignal $s) => $s->targets['proposal_ids'])->all();
        sort($pairs);
        $expected = [[min($a->id, $b->id), max($a->id, $b->id)], [min($b->id, $same->id), max($b->id, $same->id)]];
        sort($expected);
        $this->assertSame($expected, $pairs);
    }

    public function test_l_activite_nocturne_est_signalee_a_partir_d_un_volume_minimal(): void
    {
        config(['votalis.integrity.night_share' => 0.5, 'votalis.integrity.night_min_votes' => 4]);
        $proposal = Proposal::factory()->create();
        $service = app(VoteService::class);
        $this->travelTo(now()->setTime(3, 0));
        foreach (range(1, 3) as $i) {
            $service->cast(User::factory()->create(), $proposal, VoteValue::Yes, VoteValue::Yes, null, false);
        }
        $this->travelTo(now()->setTime(15, 0));
        $service->cast(User::factory()->create(), $proposal, VoteValue::Yes, VoteValue::Yes, null, false);

        app(IntegrityScanner::class)->scan();

        $signal = IntegritySignal::query()->where('type', SignalType::AtypicalHours->value)->sole();
        $this->assertSame(3, $signal->details['night_votes']);
        $this->assertSame(4, $signal->details['total_votes']);
    }

    public function test_les_signaux_sont_visibles_de_la_moderation_et_de_l_administrateur_en_lecture_seule(): void
    {
        $signal = IntegritySignal::create(['type' => SignalType::VoteSpike, 'severity' => 2, 'targets' => ['proposal_id' => 1], 'details' => ['count' => 10], 'window_date' => now()->toDateString(), 'status' => SignalStatus::New]);

        $this->get('/moderation/signaux')->assertRedirect('/connexion');
        $this->actingAs(User::factory()->create())->get('/moderation/signaux')->assertForbidden();

        $admin = User::factory()->role(Role::Admin)->withTwoFactor()->create();
        $this->actingAs($admin)->get('/moderation/signaux')->assertOk()->assertSee('Pic de votes')->assertDontSee('Statut du signal');
        $this->actingAs($admin)->post("/moderation/signaux/{$signal->id}", ['status' => 'confirmed'])->assertForbidden();

        $moderator = User::factory()->role(Role::Moderator)->withTwoFactor()->create();
        $this->actingAs($moderator)->get('/moderation/signaux')->assertOk()->assertSee('Statut du signal');
        $this->actingAs($moderator)->post("/moderation/signaux/{$signal->id}", ['status' => 'confirmed'])->assertRedirect('/moderation/signaux');
        $this->assertSame(SignalStatus::Confirmed, $signal->fresh()->status);
        $this->assertSame($moderator->id, $signal->fresh()->reviewed_by);
    }
}
