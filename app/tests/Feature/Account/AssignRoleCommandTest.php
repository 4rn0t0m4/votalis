<?php

namespace Tests\Feature\Account;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_attribue_un_role_par_pseudonyme(): void
    {
        $user = User::factory()->create(['pseudonym' => 'Citoyenne_42']);

        $this->artisan('role:assign', ['pseudonym' => 'citoyenne_42', 'role' => 'moderator'])
            ->expectsOutputToContain('Modérateur')
            ->expectsOutputToContain('double authentification')
            ->assertSuccessful();

        $this->assertSame(Role::Moderator, $user->fresh()->role);
    }

    public function test_refuse_un_role_inconnu_et_un_pseudonyme_inconnu(): void
    {
        User::factory()->create(['pseudonym' => 'Citoyenne_42']);

        $this->artisan('role:assign', ['pseudonym' => 'Citoyenne_42', 'role' => 'superuser'])->assertFailed();
        $this->artisan('role:assign', ['pseudonym' => 'inconnu', 'role' => 'admin'])->assertFailed();
    }
}
