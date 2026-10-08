<?php

namespace App\Services;

use App\Enums\ActorRole;
use App\Enums\AppealStatus;
use App\Enums\ArgumentStatus;
use App\Enums\ModerationAction;
use App\Enums\ProposalStatus;
use App\Models\Appeal;
use App\Models\Argument;
use App\Models\ModerationLogEntry;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\ModerationNotice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Contestation d'une décision (CDC section 6) : l'auteur conteste une fois, dans le délai ;
 * le comité éditorial tranche, et jamais l'auteur de la décision contestée (critère D2).
 */
class AppealService
{
    /**
     * @throws ValidationException
     */
    public function file(User $author, ModerationLogEntry $entry, string $body): Appeal
    {
        Gate::forUser($author)->authorize('appeal', $entry);

        $body = trim($body);

        if (mb_strlen($body) < 20 || mb_strlen($body) > 1000) {
            throw ValidationException::withMessages(['body' => [__('Expliquez votre contestation en 20 à 1 000 caractères.')]]);
        }

        return Appeal::create([
            'log_entry_id' => $entry->id,
            'author_id' => $author->id,
            'body' => $body,
            'status' => AppealStatus::Pending,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function decide(User $arbiter, Appeal $appeal, bool $overturn, ?string $note = null): Appeal
    {
        Gate::forUser($arbiter)->authorize('arbitrate-appeals');

        if (! $appeal->isPending()) {
            throw ValidationException::withMessages(['appeal' => [__('Cette contestation a déjà été tranchée.')]]);
        }

        $contested = $appeal->logEntry;

        if ($contested->actor_id === $arbiter->id) {
            throw ValidationException::withMessages(['appeal' => [__('Vous ne pouvez pas trancher la contestation de votre propre décision : un autre membre du comité doit le faire.')]]);
        }

        $note = trim((string) $note) ?: null;

        if ($note !== null && mb_strlen($note) > 500) {
            throw ValidationException::withMessages(['decision_note' => [__('La motivation ne dépasse pas 500 caractères.')]]);
        }

        return DB::transaction(function () use ($arbiter, $appeal, $contested, $overturn, $note): Appeal {
            if ($overturn) {
                $this->restore($contested);
            }

            $entry = ModerationLogEntry::create([
                'target_type' => $contested->target_type,
                'target_id' => $contested->target_id,
                'action' => $overturn ? ModerationAction::AppealOverturned : ModerationAction::AppealConfirmed,
                'motive' => $contested->motive,
                'actor_id' => $arbiter->id,
                'actor_role' => ActorRole::Editorial,
                'details' => ['appealed_entry_id' => $contested->id],
            ]);

            $appeal->forceFill([
                'status' => $overturn ? AppealStatus::Overturned : AppealStatus::Confirmed,
                'decided_by' => $arbiter->id,
                'decision_note' => $note,
                'decision_log_entry_id' => $entry->id,
                'decided_at' => now(),
            ])->save();

            $appeal->author?->notify(new ModerationNotice(ModerationNotice::APPEAL_DECIDED));

            return $appeal;
        });
    }

    /** Annulation : le contenu redevient visible, ou la suspension est levée. */
    private function restore(ModerationLogEntry $contested): void
    {
        $target = $contested->target;

        if ($target instanceof Proposal) {
            $target->forceFill(['status' => ProposalStatus::Published, 'hidden_motive' => null, 'rewrite_allowed_until' => null])->save();
        } elseif ($target instanceof Argument) {
            $target->forceFill(['status' => ArgumentStatus::Published, 'hidden_motive' => null])->save();
        } elseif ($target instanceof User) {
            $target->forceFill(['suspended_at' => null, 'suspended_until' => null])->save();
        }
    }
}
