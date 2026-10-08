<?php

namespace Tests\Feature\Moderation;

use App\Enums\ReportMotive;
use App\Enums\Role;
use App\Enums\SignalStatus;
use App\Enums\SignalType;
use App\Models\IntegritySignal;
use App\Models\Proposal;
use App\Models\TransparencyReport;
use App\Models\User;
use App\Services\AppealService;
use App\Services\ModerationService;
use App\Services\ReportService;
use App\Services\TransparencyReporter;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TransparencyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_rapport_ne_contient_que_des_agregats_et_est_public(): void
    {
        Notification::fake();
        $editorial = User::factory()->role(Role::Editorial)->withTwoFactor()->create(['pseudonym' => 'membre_comite']);
        $author = User::factory()->create(['pseudonym' => 'auteur_signale', 'email' => 'auteur@example.org', 'created_at' => now()->subMonth()]);
        $proposal = Proposal::factory()->create(['author_id' => $author->id, 'title' => 'Titre à ne pas publier dans le rapport']);
        $reporter = User::factory()->create(['created_at' => now()->subMonth()]);

        app(ReportService::class)->report($reporter, $proposal, ReportMotive::Spam);
        app(ReportService::class)->report(User::factory()->create(['created_at' => now()->subMonth()]), Proposal::factory()->create(), ReportMotive::OffTopic);
        $entry = app(ModerationService::class)->hide($editorial, $proposal, ReportMotive::Spam);
        $appeal = app(AppealService::class)->file($author, $entry, 'Ce n’est pas du spam, la proposition est argumentée et sourcée.');
        app(AppealService::class)->decide(User::factory()->role(Role::Editorial)->withTwoFactor()->create(), $appeal, false);
        app(ModerationService::class)->suspend($editorial, $author, ReportMotive::CoordinatedCampaign, 30);
        IntegritySignal::create(['type' => SignalType::IdenticalVoting, 'severity' => 3, 'targets' => ['user_ids' => [$author->id]], 'window_date' => now()->toDateString(), 'status' => SignalStatus::Confirmed, 'reviewed_at' => now(), 'reviewed_by' => $editorial->id]);

        $report = app(TransparencyReporter::class)->generate(now()->subDay(), now()->addDay());
        $data = $report->data;

        $this->assertSame(2, $data['reports']['total']);
        $this->assertSame(1, $data['reports']['by_motive']['spam']);
        $this->assertSame(1, $data['reports']['by_motive']['off_topic']);
        $this->assertSame(1, $data['decisions']['by_action']['hide']);
        $this->assertSame(1, $data['decisions']['by_action']['appeal_confirmed']);
        $this->assertSame(1, $data['appeals']['filed']);
        $this->assertSame(1, $data['appeals']['confirmed']);
        $this->assertSame(0, $data['appeals']['overturned']);
        $this->assertSame(1, $data['suspensions']);
        $this->assertSame(1, $data['coordinated_operations']);

        $json = json_encode($data, JSON_THROW_ON_ERROR);
        foreach (['membre_comite', 'auteur_signale', 'auteur@example.org', 'Titre à ne pas publier', (string) $author->id.'"', 'user_ids', 'pseudonym'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }

        $this->get('/transparence')->assertOk()->assertSee('Rapports de transparence')->assertSee('Comptes suspendus')->assertSee('Opérations coordonnées confirmées')->assertDontSee('auteur_signale')->assertDontSee('membre_comite');
    }

    public function test_la_commande_genere_le_trimestre_precedent_et_une_periode_donnee(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1));
        $this->artisan('transparency:report')->assertSuccessful();
        $report = TransparencyReport::sole();
        $this->assertSame('2026-07-01', $report->period_start->toDateString());
        $this->assertSame('2026-09-30', $report->period_end->toDateString());

        $this->artisan('transparency:report', ['--from' => '2026-01-01', '--to' => '2026-03-31'])->assertSuccessful();
        $this->assertDatabaseCount('transparency_reports', 2);

        // Regénérer la même période met à jour le rapport au lieu de le dupliquer.
        $this->artisan('transparency:report')->assertSuccessful();
        $this->assertDatabaseCount('transparency_reports', 2);
    }

    public function test_les_taches_sont_planifiees(): void
    {
        $events = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command ?? '')->implode("\n");

        $this->assertStringContainsString('integrity:scan', $events);
        $this->assertStringContainsString('transparency:report', $events);
    }
}
