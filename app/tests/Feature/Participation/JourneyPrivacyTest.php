<?php

namespace Tests\Feature\Participation;

use App\Enums\ArgumentSide;
use App\Enums\Milestone;
use App\Enums\ReportMotive;
use App\Enums\Role;
use App\Enums\VoteValue;
use App\Livewire\ArgumentColumn;
use App\Livewire\VoteBox;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\User;
use App\Services\Journey;
use App\Services\ReportService;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** F5 : rien du parcours d'un participant n'est visible d'un autre compte ni du public. */
class JourneyPrivacyTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $forbidden;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forbidden = array_merge(
            array_map(fn (Milestone $m) => $m->value, Milestone::cases()),
            array_map(fn (Milestone $m) => $m->label(), Milestone::cases()),
            ['Jalon atteint', 'Mon parcours'],
        );
    }

    public function test_les_pages_publiques_et_de_moderation_ne_montrent_aucun_jalon_d_autrui(): void
    {
        $author = User::factory()->create(['pseudonym' => 'Auteur_Prolifique', 'created_at' => now()->subMonth()]);
        $proposal = Proposal::factory()->create(['author_id' => $author->id, 'title' => 'Instaurer une mesure visible']);
        $argument = Argument::factory()->create(['proposal_id' => $proposal->id, 'author_id' => $author->id, 'side' => ArgumentSide::For, 'source_url' => 'https://www.exemple.gouv.fr/x']);
        $other = Proposal::factory()->create();
        app(VoteService::class)->cast($author, $other, VoteValue::Yes, VoteValue::Yes, 'elle soit évaluée', true);
        $this->assertNotEmpty(app(Journey::class)->reached($author));

        $visitor = User::factory()->create(['created_at' => now()->subMonth()]);
        $moderator = User::factory()->role(Role::Moderator)->withTwoFactor()->create();
        app(ReportService::class)->report($visitor, $argument, ReportMotive::OffTopic, null);

        $pages = [
            [null, $proposal->url()],
            [null, $other->url()],
            [null, '/recherche?q=visible'],
            [null, '/journal-de-moderation'],
            [$visitor, $proposal->url()],
            [$moderator, '/moderation'],
            [$moderator, "/moderation/dossiers/argument/{$argument->id}"],
        ];

        foreach ($pages as [$as, $url]) {
            $response = $as === null ? $this->get($url) : $this->actingAs($as)->get($url);
            $response->assertStatus(200);
            $html = (string) $response->getContent();

            foreach ($this->forbidden as $needle) {
                $this->assertStringNotContainsString($needle, $html, "« {$needle} » apparaît sur {$url}");
            }
        }

        $voteBox = Livewire::actingAs($visitor)->test(VoteBox::class, ['proposal' => $proposal]);
        $column = Livewire::actingAs($visitor)->test(ArgumentColumn::class, ['proposal' => $proposal, 'side' => ArgumentSide::For]);

        foreach ($this->forbidden as $needle) {
            $voteBox->assertDontSee($needle);
            $column->assertDontSee($needle);
        }
    }

    public function test_le_modele_utilisateur_serialise_ne_contient_pas_de_jalon(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        app(VoteService::class)->cast($user, Proposal::factory()->create(), VoteValue::Yes, VoteValue::Yes, null, false);

        $this->assertStringNotContainsString('premiere_voix', (string) json_encode($user->fresh()));
    }
}
