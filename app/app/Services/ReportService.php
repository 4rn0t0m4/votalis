<?php

namespace App\Services;

use App\Enums\ActorRole;
use App\Enums\ArgumentStatus;
use App\Enums\ModerationAction;
use App\Enums\ProposalStatus;
use App\Enums\ReportMotive;
use App\Models\Argument;
use App\Models\ModerationLogEntry;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Seul point de création d'un signalement (CDC 4.8). Un contenu signalé reste visible
 * jusqu'à la décision, sauf « contenu illégal » : masqué immédiatement, et journalisé.
 */
class ReportService
{
    public function __construct(private readonly ContributionCaps $caps) {}

    /**
     * @throws ValidationException
     */
    public function report(User $reporter, Proposal|Argument $target, ReportMotive $motive, ?string $details = null): Report
    {
        app(ReadOnlyMode::class)->assertWritable();

        Gate::forUser($reporter)->authorize('report', $target);

        if ($target->reports()->where('reporter_id', $reporter->id)->exists()) {
            throw ValidationException::withMessages(['motive' => [__('Vous avez déjà signalé ce contenu.')]]);
        }

        $this->caps->assertCanReport($reporter);

        $details = trim((string) $details) ?: null;

        if ($details !== null && mb_strlen($details) > 300) {
            throw ValidationException::withMessages(['details' => [__('La précision ne dépasse pas 300 caractères.')]]);
        }

        return DB::transaction(function () use ($reporter, $target, $motive, $details): Report {
            $report = $target->reports()->create([
                'reporter_id' => $reporter->id,
                'motive' => $motive,
                'details' => $details,
            ]);

            if ($motive->hidesImmediately() && $target->isPublished()) {
                $this->hidePending($target, $motive);
            }

            return $report;
        });
    }

    private function hidePending(Proposal|Argument $target, ReportMotive $motive): void
    {
        app(PublicPageCache::class)->flush();
        $target->forceFill([
            'status' => $target instanceof Proposal ? ProposalStatus::Hidden : ArgumentStatus::Hidden,
            'hidden_motive' => $motive,
        ])->save();

        ModerationLogEntry::create([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->id,
            'action' => ModerationAction::AutoHide,
            'motive' => $motive,
            'actor_id' => null,
            'actor_role' => ActorRole::System,
        ]);
    }
}
