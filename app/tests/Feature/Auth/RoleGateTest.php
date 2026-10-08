<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleGateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Matrice de la section 3 du cahier des charges.
     *
     * @return array<string, array{Role, list<string>}>
     */
    public static function roles(): array
    {
        return [
            'participant' => [Role::Participant, ['participate']],
            'modérateur' => [Role::Moderator, ['participate', 'moderate']],
            'comité éditorial' => [Role::Editorial, ['participate', 'moderate', 'manage-themes', 'manage-tradeoffs', 'publish-synthesis', 'arbitrate-appeals']],
            'administrateur technique' => [Role::Admin, ['manage-platform']],
        ];
    }

    /**
     * @param  list<string>  $allowed
     */
    #[DataProvider('roles')]
    public function test_chaque_role_n_a_que_ses_capacites(Role $role, array $allowed): void
    {
        $user = User::factory()->role($role)->create();

        foreach (Role::allAbilities() as $ability) {
            $this->assertSame(
                in_array($ability, $allowed, true),
                Gate::forUser($user)->allows($ability),
                "Rôle {$role->value}, capacité {$ability}"
            );
        }
    }

    public function test_l_administrateur_technique_n_a_aucun_pouvoir_editorial(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->assertFalse(Gate::forUser($admin)->allows('moderate'));
        $this->assertFalse(Gate::forUser($admin)->allows('manage-themes'));
        $this->assertFalse(Gate::forUser($admin)->allows('participate'));
        $this->assertTrue(Gate::forUser($admin)->allows('manage-platform'));
    }

    public function test_un_visiteur_n_a_aucune_capacite(): void
    {
        foreach (Role::allAbilities() as $ability) {
            $this->assertFalse(Gate::allows($ability));
        }
    }

    public function test_le_role_par_defaut_est_participant_et_la_base_refuse_un_role_inconnu(): void
    {
        $user = User::factory()->create();
        $this->assertSame(Role::Participant, $user->fresh()->role);

        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $user->id)->update(['role' => 'superuser']);
    }
}
