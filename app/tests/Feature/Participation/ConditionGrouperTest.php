<?php

namespace Tests\Feature\Participation;

use App\Enums\VoteValue;
use App\Models\Proposal;
use App\Models\User;
use App\Models\VoteConditionGroup;
use App\Services\ConditionGrouper;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConditionGrouperTest extends TestCase
{
    use RefreshDatabase;

    public function test_regroupe_les_conditions_proches_et_choisit_un_libelle_central(): void
    {
        Http::fake(['*/embed' => Http::response(['model' => 'x', 'dimension' => 3, 'vectors' => [
            [1.0, 0.0, 0.0],
            [0.98, 0.2, 0.0],
            [0.0, 1.0, 0.0],
            [0.99, 0.1, 0.0],
        ]])]);

        $groups = app(ConditionGrouper::class)->group([
            'qu’elle soit évaluée au bout de trois ans',
            'qu’une évaluation ait lieu après 3 ans',
            'que les PME soient exonérées',
            'qu’elle soit évaluée après trois ans',
        ]);

        $this->assertCount(2, $groups);
        $this->assertSame(3, $groups[0]['count']);
        $this->assertSame('qu’elle soit évaluée après trois ans', $groups[0]['label']);
        $this->assertSame(['que les PME soient exonérées'], $groups[1]['conditions']);
    }

    public function test_sans_service_chaque_condition_forme_son_groupe(): void
    {
        Http::fake(['*/*' => Http::response('', 503)]);

        $groups = app(ConditionGrouper::class)->group(['a', 'b']);

        $this->assertCount(2, $groups);
    }

    public function test_un_vote_conditionnel_declenche_le_regroupement_affiche_dans_les_resultats(): void
    {
        Http::fake(['*/embed' => Http::response(['model' => 'x', 'dimension' => 3, 'vectors' => [[1, 0, 0], [1, 0, 0]]])]);
        $proposal = Proposal::factory()->create();
        $service = app(VoteService::class);

        foreach (['qu’elle soit évaluée', 'qu’elle soit bien évaluée'] as $condition) {
            $service->cast(User::factory()->create(['created_at' => now()->subMonth()]), $proposal, VoteValue::Yes, VoteValue::Yes, $condition, false);
        }

        $this->assertSame(1, VoteConditionGroup::query()->where('proposal_id', $proposal->id)->count());
        $this->assertSame(['qu’elle soit évaluée (2)'], $service->results($proposal)['conditions']);
    }
}
