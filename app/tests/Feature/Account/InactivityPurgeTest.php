<?php

namespace Tests\Feature\Account;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\InactivityNotice;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InactivityPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_date_de_derniere_visite_est_ecrite_au_jour_pres_une_fois_par_jour(): void
    {
        $user = User::factory()->create(['last_seen_at' => now()->subDays(10)->toDateString(), 'inactivity_notice_sent_at' => now()->subDay()]);

        $this->actingAs($user)->get('/mon-compte')->assertOk();

        $user->refresh();
        $this->assertSame(today()->toDateString(), $user->last_seen_at?->toDateString());
        $this->assertNull($user->inactivity_notice_sent_at);
        $this->assertSame(today()->toDateString(), $user->fresh()->last_seen_at?->toDateString());
    }

    public function test_les_comptes_inactifs_sont_prevenus_puis_supprimes_les_autres_jamais(): void
    {
        Notification::fake();
        config(['votalis.retention.inactive_months' => 36, 'votalis.retention.notice_days' => 30]);

        $inactive = User::factory()->create(['last_seen_at' => now()->subMonths(36)->subDays(5)->toDateString()]);
        $almost = User::factory()->create(['last_seen_at' => now()->subMonths(36)->addDays(10)->toDateString()]);
        $neverSeenOld = User::factory()->create(['last_seen_at' => null, 'created_at' => now()->subMonths(40)]);
        $active = User::factory()->create(['last_seen_at' => now()->subMonth()->toDateString()]);
        $moderator = User::factory()->role(Role::Moderator)->withTwoFactor()->create(['last_seen_at' => now()->subMonths(40)->toDateString()]);

        // Simulation : rien n'est envoyé ni supprimé.
        $this->artisan('accounts:purge-inactive', ['--dry-run' => true])->assertSuccessful();
        Notification::assertNothingSent();

        // Premier passage : préavis aux inactifs, pas de suppression.
        $this->artisan('accounts:purge-inactive')->assertSuccessful();
        Notification::assertSentTo($inactive, InactivityNotice::class);
        Notification::assertSentTo($almost, InactivityNotice::class);
        Notification::assertSentTo($neverSeenOld, InactivityNotice::class);
        Notification::assertNotSentTo($active, InactivityNotice::class);
        Notification::assertNotSentTo($moderator, InactivityNotice::class);
        $this->assertDatabaseCount('users', 5);

        // Pendant le préavis : rien.
        $this->travel(29)->days();
        $this->artisan('accounts:purge-inactive')->assertSuccessful();
        $this->assertDatabaseCount('users', 5);

        // `almost` se reconnecte : son préavis est annulé.
        $this->actingAs($almost)->get('/mon-compte')->assertOk();

        // Préavis écoulé : suppression des inactifs prévenus, les autres restent.
        $this->travel(2)->days();
        $this->artisan('accounts:purge-inactive')->assertSuccessful();
        $this->assertDatabaseMissing('users', ['id' => $inactive->id]);
        $this->assertDatabaseMissing('users', ['id' => $neverSeenOld->id]);
        $this->assertDatabaseHas('users', ['id' => $almost->id]);
        $this->assertDatabaseHas('users', ['id' => $active->id]);
        $this->assertDatabaseHas('users', ['id' => $moderator->id]);
    }

    public function test_les_purges_sont_planifiees(): void
    {
        $events = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command ?? '')->implode("\n");

        $this->assertStringContainsString('accounts:purge-inactive', $events);
        $this->assertStringContainsString('auth:clear-resets', $events);
    }
}
