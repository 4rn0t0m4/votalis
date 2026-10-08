<?php

namespace App\Services;

use App\Jobs\RegroupVoteConditions;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Suppression d'un compte (CDC section 8), en libre-service ou par la purge d'inactivité.
 * Les votes, réponses d'arbitrage, marques et clés d'accès disparaissent ; les propositions
 * et arguments publiés restent, rattachés à « participant supprimé » ; signalements et
 * contestations restent sans auteur ni texte libre ; le journal de modération est intact.
 */
class AccountEraser
{
    public const ERASED_TEXT = 'Texte supprimé à la demande de l’auteur.';

    /**
     * @throws ValidationException
     */
    public function erase(User $user, string $reason = 'self-service'): void
    {
        if ($user->role->isPrivileged()) {
            throw ValidationException::withMessages(['account' => [__('Un compte avec un rôle privilégié doit d’abord être ramené au rôle participant par l’administrateur technique.')]]);
        }

        $votedProposalIds = $user->votes()->pluck('proposal_id')->all();

        DB::transaction(function () use ($user, $votedProposalIds): void {
            // Textes libres anonymisés avant la suppression (les clés étrangères passent à null).
            $user->appeals()->update(['body' => self::ERASED_TEXT]);
            $user->reports()->update(['details' => null]);

            // Compteurs dénormalisés tenus à jour avant la cascade sur `votes`.
            if ($votedProposalIds !== []) {
                Proposal::query()->whereIn('id', $votedProposalIds)->decrement('votes_count');
            }

            DB::table('password_reset_tokens')->where('email', $user->email_hash)->delete();

            // Cascade : votes, réponses et historiques d'arbitrage, marques « utile », clés d'accès.
            // Mise à null : propositions, arguments, révisions, signalements, contestations, suggestions.
            $user->delete();
        });

        foreach ($votedProposalIds as $proposalId) {
            RegroupVoteConditions::dispatch($proposalId);
        }

        // Comptage anonyme : ni identifiant, ni pseudonyme, ni e-mail.
        Log::info('Compte supprimé', ['reason' => $reason, 'votes' => count($votedProposalIds)]);
    }
}
