<?php

namespace Tests\Feature\Participation;

use App\Enums\VoteValue;
use App\Livewire\VoteBox;
use App\Models\Proposal;
use App\Models\User;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** F3 : après un vote, écran de remerciement, résultats et célébration ; la révision le retire. */
class VoteConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_vote_affiche_un_remerciement_puis_les_resultats(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $proposal = Proposal::factory()->create();
        app(VoteService::class)->cast(User::factory()->create(['created_at' => now()->subMonth()]), $proposal, VoteValue::No, VoteValue::Yes, null, false);

        $component = Livewire::actingAs($user)->test(VoteBox::class, ['proposal' => $proposal])
            ->assertDontSee('Résultats détaillés')
            ->set('desirable', 1)->set('necessary', 1)
            ->call('vote')
            ->assertHasNoErrors()
            ->assertSee('Merci. Votre avis est enregistré.')
            ->assertSee('2e personne')
            ->assertSee('Résultats détaillés')
            ->assertSee('50 %')
            ->assertSee('Jalon atteint')
            ->assertSee('Première voix');

        $component->call('revise')->assertDontSee('Merci. Votre avis est enregistré.')->assertSee('Enregistrer mon vote révisé');
        $component->call('cancel')->assertDontSee('Merci. Votre avis est enregistré.')->assertDontSee('Jalon atteint')->assertSee('Résultats détaillés');
    }

    public function test_les_regles_de_vote_restent_appliquees(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $own = Proposal::factory()->create(['author_id' => $user->id]);

        Livewire::actingAs($user)->test(VoteBox::class, ['proposal' => $own])->assertSee('vous ne votez pas dessus')->assertDontSee('Souhaitable pour vous ?');
    }
}
