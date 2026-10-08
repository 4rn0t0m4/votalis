<?php

namespace Tests\Feature\Content;

use App\Enums\ArgumentSide;
use App\Livewire\ArgumentColumn;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArgumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_participant_ajoute_un_argument_pour(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $proposal = Proposal::factory()->create();

        Livewire::actingAs($user)->test(ArgumentColumn::class, ['proposal' => $proposal, 'side' => ArgumentSide::For])
            ->set('body', 'Cette mesure réduit les inégalités d’accès aux soins dans les zones rurales.')
            ->set('source_url', 'https://www.exemple.gouv.fr/etude')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Cette mesure réduit les inégalités');

        $argument = Argument::sole();
        $this->assertSame(ArgumentSide::For, $argument->side);
        $this->assertSame($user->id, $argument->author_id);
    }

    public function test_les_limites_sont_appliquees(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $proposal = Proposal::factory()->create();

        Livewire::actingAs($user)->test(ArgumentColumn::class, ['proposal' => $proposal, 'side' => ArgumentSide::Against])
            ->set('body', str_repeat('a', 601))->call('submit')->assertHasErrors(['body'])
            ->set('body', 'Un argument valable mais avec une source invalide.')->set('source_url', 'pas une url')->call('submit')->assertHasErrors(['source_url']);

        $this->assertDatabaseCount('arguments', 0);
    }

    public function test_un_visiteur_ne_peut_ni_argumenter_ni_marquer(): void
    {
        $proposal = Proposal::factory()->create();
        $argument = Argument::factory()->create(['proposal_id' => $proposal->id, 'side' => ArgumentSide::For]);

        Livewire::test(ArgumentColumn::class, ['proposal' => $proposal, 'side' => ArgumentSide::For])
            ->assertSee('Connectez-vous')
            ->set('body', 'Tentative sans compte, qui doit être refusée côté serveur.')
            ->call('submit')->assertForbidden();

        Livewire::test(ArgumentColumn::class, ['proposal' => $proposal, 'side' => ArgumentSide::For])
            ->call('toggleMark', $argument->id)->assertForbidden();

        $this->assertDatabaseCount('arguments', 1);
        $this->assertDatabaseCount('argument_marks', 0);
    }

    public function test_la_marque_utile_est_unique_et_retirable(): void
    {
        $user = User::factory()->create();
        $proposal = Proposal::factory()->create();
        $argument = Argument::factory()->create(['proposal_id' => $proposal->id, 'side' => ArgumentSide::Against]);

        $component = Livewire::actingAs($user)->test(ArgumentColumn::class, ['proposal' => $proposal, 'side' => ArgumentSide::Against]);

        $component->call('toggleMark', $argument->id)->assertSee('Utile · 1');
        $this->assertDatabaseCount('argument_marks', 1);

        $component->call('toggleMark', $argument->id)->assertSee('Utile · 0');
        $this->assertDatabaseCount('argument_marks', 0);
    }

    public function test_le_plafond_d_arguments_par_jour_est_applique(): void
    {
        config(['votalis.caps.arguments_per_day' => 2]);
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
        $proposal = Proposal::factory()->create();

        $component = Livewire::actingAs($user)->test(ArgumentColumn::class, ['proposal' => $proposal, 'side' => ArgumentSide::For]);

        $component->set('body', 'Premier argument suffisamment long pour passer.')->call('submit')->assertHasNoErrors();
        $component->set('body', 'Deuxième argument suffisamment long pour passer.')->call('submit')->assertHasNoErrors();
        $component->set('body', 'Troisième argument qui dépasse le plafond du jour.')->call('submit')->assertHasErrors(['cap']);

        $this->assertDatabaseCount('arguments', 2);
    }

    public function test_les_deux_colonnes_sont_rendues_sur_la_fiche(): void
    {
        $proposal = Proposal::factory()->create();
        Argument::factory()->create(['proposal_id' => $proposal->id, 'side' => ArgumentSide::For, 'body' => 'Argument favorable distinctif.']);
        Argument::factory()->create(['proposal_id' => $proposal->id, 'side' => ArgumentSide::Against, 'body' => 'Argument défavorable distinctif.']);

        $this->get($proposal->url())->assertOk()->assertSeeInOrder(['Pour', 'Argument favorable distinctif.', 'Contre', 'Argument défavorable distinctif.']);
    }
}
