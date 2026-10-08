<?php

namespace Tests\Feature\Content;

use App\Enums\Role;
use App\Enums\ThemeStatus;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThemeAdminTest extends TestCase
{
    use RefreshDatabase;

    private function editorial(): User
    {
        return User::factory()->role(Role::Editorial)->withTwoFactor()->create();
    }

    public function test_seul_le_comite_editorial_gere_les_themes(): void
    {
        $this->actingAs(User::factory()->create())->get('/comite/themes')->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Moderator)->withTwoFactor()->create())->get('/comite/themes')->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Admin)->withTwoFactor()->create())->get('/comite/themes')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/comite/themes', ['name' => 'Santé', 'status' => 'open'])->assertForbidden();

        $this->actingAs($this->editorial())->get('/comite/themes')->assertOk();
        $this->assertDatabaseCount('themes', 0);
    }

    public function test_le_comite_cree_un_theme_avec_un_slug_unique(): void
    {
        $editorial = $this->editorial();

        $this->actingAs($editorial)->post('/comite/themes', ['name' => 'Santé', 'status' => 'open', 'description' => 'Hôpital et soins.'])->assertRedirect('/comite/themes');
        $this->actingAs($editorial)->post('/comite/themes', ['name' => 'Santé', 'status' => 'open'])->assertRedirect('/comite/themes');

        $this->assertSame(['sante', 'sante-2'], Theme::query()->orderBy('id')->pluck('slug')->all());
    }

    public function test_deux_niveaux_maximum(): void
    {
        $editorial = $this->editorial();
        $root = Theme::factory()->create();
        $child = Theme::factory()->childOf($root)->create();

        $this->actingAs($editorial)
            ->post('/comite/themes', ['name' => 'Petit-enfant', 'status' => 'open', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($editorial)
            ->put("/comite/themes/{$root->id}", ['name' => $root->name, 'status' => 'open', 'parent_id' => Theme::factory()->create()->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseCount('themes', 3);
    }

    public function test_un_theme_s_archive_et_disparait_des_listes_mais_reste_accessible(): void
    {
        $theme = Theme::factory()->create(['name' => 'Logement']);

        $this->actingAs($this->editorial())
            ->put("/comite/themes/{$theme->id}", ['name' => 'Logement', 'status' => 'archived'])
            ->assertRedirect('/comite/themes');

        $this->assertSame(ThemeStatus::Archived, $theme->fresh()->status);
        $this->flushSession();
        $this->get('/themes')->assertOk()->assertDontSee('Logement');
        $this->get("/themes/{$theme->slug}")->assertOk()->assertSee('archivé');
    }

    public function test_la_base_refuse_un_statut_inconnu(): void
    {
        $theme = Theme::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('themes')->where('id', $theme->id)->update(['status' => 'bizarre']);
    }
}
