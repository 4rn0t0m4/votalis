<?php

namespace App\Services;

use App\Enums\ActorRole;
use App\Enums\ArgumentStatus;
use App\Enums\ModerationAction;
use App\Enums\ProposalStatus;
use App\Enums\ReportMotive;
use App\Enums\ReportStatus;
use App\Models\Argument;
use App\Models\ModerationLogEntry;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\ModerationNotice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Seul point d'action d'un modérateur sur un contenu (CDC section 6) : conserver,
 * masquer, demander une reformulation. Chaque action écrit une entrée du journal
 * public dans la même transaction et clôt les signalements ouverts du dossier.
 */
class ModerationService
{
    /** Conserver : le contenu redevient visible s'il avait été masqué en attente de décision. */
    public function keep(User $moderator, Proposal|Argument $target): ModerationLogEntry
    {
        Gate::forUser($moderator)->authorize('moderate');

        return DB::transaction(function () use ($moderator, $target): ModerationLogEntry {
            if ($target->isHidden()) {
                $this->publish($target);
            }

            return $this->close($target, $this->log($moderator, $target, ModerationAction::Keep));
        });
    }

    /**
     * Masquer avec motif. Pour un doublon, la fiche conservée est référencée par son identifiant.
     *
     * @throws ValidationException
     */
    public function hide(User $moderator, Proposal|Argument $target, ReportMotive $motive, ?int $keptProposalId = null): ModerationLogEntry
    {
        Gate::forUser($moderator)->authorize('moderate');

        $details = null;

        if ($motive === ReportMotive::Duplicate) {
            if ($target instanceof Proposal) {
                $kept = Proposal::query()->published()->whereKeyNot($target->id)->find($keptProposalId);

                if ($kept === null) {
                    throw ValidationException::withMessages(['kept_proposal_id' => [__('Indiquez la fiche conservée (numéro d’une proposition publiée).')]]);
                }

                $details = ['kept_proposal_id' => $kept->id];
            }
        }

        return DB::transaction(function () use ($moderator, $target, $motive, $details): ModerationLogEntry {
            $target->forceFill([
                'status' => $target instanceof Proposal ? ProposalStatus::Hidden : ArgumentStatus::Hidden,
                'hidden_motive' => $motive,
                ...($target instanceof Proposal ? ['rewrite_allowed_until' => null] : []),
            ])->save();

            $entry = $this->close($target, $this->log($moderator, $target, ModerationAction::Hide, $motive, $details));
            $target->author?->notify(new ModerationNotice(ModerationNotice::DECISION));

            return $entry;
        });
    }

    /**
     * Demander une reformulation : la fiche devient invisible et son auteur peut en modifier
     * le fond une fois, malgré le verrou du premier vote. Réservé aux propositions.
     *
     * @throws ValidationException
     */
    public function requestRewrite(User $moderator, Proposal $target, ReportMotive $motive): ModerationLogEntry
    {
        Gate::forUser($moderator)->authorize('moderate');

        if ($target->author_id === null) {
            throw ValidationException::withMessages(['action' => [__('Cette fiche n’a plus d’auteur : masquez-la ou conservez-la.')]]);
        }

        return DB::transaction(function () use ($moderator, $target, $motive): ModerationLogEntry {
            $target->forceFill([
                'status' => ProposalStatus::RewriteRequested,
                'hidden_motive' => $motive,
                'rewrite_allowed_until' => now()->addDays((int) config('votalis.moderation.rewrite_days', 14)),
            ])->save();

            $entry = $this->close($target, $this->log($moderator, $target, ModerationAction::RequestRewrite, $motive));
            $target->author?->notify(new ModerationNotice(ModerationNotice::DECISION));

            return $entry;
        });
    }

    /**
     * Suspendre un compte participant : réservé au comité éditorial, journalisé sans pseudonyme,
     * contestable. Le compte peut encore lire et contester (Gate `participate`).
     *
     * @param  int|null  $days  Durée en jours ; null pour une suspension sans terme.
     *
     * @throws ValidationException
     */
    public function suspend(User $editorial, User $target, ReportMotive $motive, ?int $days = null): ModerationLogEntry
    {
        Gate::forUser($editorial)->authorize('arbitrate-appeals');

        if ($target->id === $editorial->id || $target->role->isPrivileged()) {
            throw ValidationException::withMessages(['suspend' => [__('Seul un compte participant peut être suspendu.')]]);
        }

        if ($days !== null && ($days < 1 || $days > 3650)) {
            throw ValidationException::withMessages(['days' => [__('La durée est comprise entre 1 et 3650 jours, ou vide pour une suspension sans terme.')]]);
        }

        return DB::transaction(function () use ($editorial, $target, $motive, $days): ModerationLogEntry {
            $target->forceFill([
                'suspended_at' => now(),
                'suspended_until' => $days === null ? null : now()->addDays($days),
            ])->save();

            $entry = ModerationLogEntry::create([
                'target_type' => 'user',
                'target_id' => $target->id,
                'action' => ModerationAction::Suspend,
                'motive' => $motive,
                'actor_id' => $editorial->id,
                'actor_role' => ActorRole::Editorial,
                'details' => $days === null ? null : ['days' => $days],
            ]);

            $target->notify(new ModerationNotice(ModerationNotice::ACCOUNT));

            return $entry;
        });
    }

    /** Appelé par ProposalService quand l'auteur a enregistré sa reformulation. */
    public function rewriteReceived(Proposal $target): ModerationLogEntry
    {
        $motive = $target->hidden_motive;
        $this->publish($target);

        return ModerationLogEntry::create([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->id,
            'action' => ModerationAction::RewriteReceived,
            'motive' => $motive,
            'actor_id' => null,
            'actor_role' => ActorRole::System,
        ]);
    }

    private function publish(Proposal|Argument $target): void
    {
        $target->forceFill([
            'status' => $target instanceof Proposal ? ProposalStatus::Published : ArgumentStatus::Published,
            'hidden_motive' => null,
            ...($target instanceof Proposal ? ['rewrite_allowed_until' => null] : []),
        ])->save();
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    private function log(User $moderator, Proposal|Argument $target, ModerationAction $action, ?ReportMotive $motive = null, ?array $details = null): ModerationLogEntry
    {
        return ModerationLogEntry::create([
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->id,
            'action' => $action,
            'motive' => $motive,
            'actor_id' => $moderator->id,
            'actor_role' => ActorRole::fromRole($moderator->role),
            'details' => $details,
        ]);
    }

    private function close(Proposal|Argument $target, ModerationLogEntry $entry): ModerationLogEntry
    {
        $target->reports()->where('status', ReportStatus::Open)->update([
            'status' => ReportStatus::Handled,
            'log_entry_id' => $entry->id,
        ]);

        return $entry;
    }
}
