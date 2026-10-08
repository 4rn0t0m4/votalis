<?php

namespace App\Services;

use App\Models\Appeal;
use App\Models\Argument;
use App\Models\ModerationLogEntry;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\TradeoffAnswer;
use App\Models\TradeoffSuggestion;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Collection;

/**
 * Export des données d'un compte (CDC section 8, droit d'accès et de portabilité).
 * Seule source de l'export : chaque bloc ne contient que les données du compte, jamais
 * le pseudonyme ni l'identifiant d'un autre participant.
 */
class AccountExporter
{
    /**
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        return [
            'exporte_le' => now()->toIso8601String(),
            'format' => 'votalis-export/1',
            'compte' => [
                'pseudonyme' => $user->pseudonym,
                'email' => $user->email,
                'role' => $user->role->label(),
                'cree_le' => $user->created_at?->toIso8601String(),
                'consentement_le' => $user->consented_at->toIso8601String(),
                'email_verifie_le' => $user->email_verified_at?->toIso8601String(),
                'double_authentification' => $user->hasEnabledTwoFactorAuthentication(),
                'cles_d_acces' => $user->passkeys()->pluck('name')->all(),
                'derniere_visite' => $user->last_seen_at?->toDateString(),
                'suspendu_depuis' => $user->suspended_at?->toIso8601String(),
            ],
            'votes' => $user->votes()->with('proposal:id,title')->get()->map(fn (Vote $v) => [
                'proposition_id' => $v->proposal_id,
                'proposition' => $v->proposal->title,
                'souhaitable' => $v->desirable->value,
                'necessaire' => $v->necessary->value,
                'condition' => $v->condition,
                'souhaitable_initial' => $v->desirable_initial->value,
                'necessaire_initial' => $v->necessary_initial->value,
                'revise_apres_arguments' => $v->revised_after_arguments,
                'cree_le' => $v->created_at?->toIso8601String(),
                'modifie_le' => $v->updated_at?->toIso8601String(),
            ])->all(),
            'propositions' => $user->proposals()->with(['sources', 'theme'])->get()->map(fn (Proposal $p) => [
                'id' => $p->id,
                'theme' => $p->theme->fullName(),
                'statut' => $p->status->value,
                'cree_le' => $p->created_at?->toIso8601String(),
                ...$p->snapshot(),
                'revisions' => $p->revisions()->where('author_id', $user->id)->get()->map(fn ($r) => [
                    'type' => $r->kind->value,
                    'date' => $r->created_at->toIso8601String(),
                    'contenu' => $r->snapshot,
                ])->all(),
            ])->all(),
            'arguments' => $user->arguments()->get()->map(fn (Argument $a) => [
                'id' => $a->id,
                'proposition_id' => $a->proposal_id,
                'cote' => $a->side->value,
                'texte' => $a->body,
                'source' => $a->source_url,
                'statut' => $a->status->value,
                'cree_le' => $a->created_at?->toIso8601String(),
            ])->all(),
            'arguments_marques_utiles' => $user->markedArguments()->pluck('arguments.id')->all(),
            'arbitrages' => $user->tradeoffAnswers()->with('tradeoff:id,title')->get()->map(fn (TradeoffAnswer $a) => [
                'arbitrage_id' => $a->tradeoff_id,
                'arbitrage' => $a->tradeoff->title,
                'mesures_choisies' => $a->item_ids,
                'conditions' => $a->conditions,
                'total' => $a->total,
                'modifie_le' => $a->updated_at?->toIso8601String(),
            ])->all(),
            'suggestions_d_arbitrage' => TradeoffSuggestion::query()->where('participant_id', $user->id)->get()->map(fn (TradeoffSuggestion $s) => [
                'arbitrage_id' => $s->tradeoff_id,
                'proposition_id' => $s->proposal_id,
                'statut' => $s->status->value,
                'cree_le' => $s->created_at?->toIso8601String(),
            ])->all(),
            'signalements_emis' => $user->reports()->get()->map(fn (Report $r) => [
                'type' => $r->target_type,
                'contenu_id' => $r->target_id,
                'motif' => $r->motive->value,
                'precision' => $r->details,
                'statut' => $r->status->value,
                'cree_le' => $r->created_at?->toIso8601String(),
            ])->all(),
            'contestations' => $user->appeals()->get()->map(fn (Appeal $a) => [
                'decision_id' => $a->log_entry_id,
                'texte' => $a->body,
                'statut' => $a->status->value,
                'motivation_du_comite' => $a->decision_note,
                'deposee_le' => $a->created_at?->toIso8601String(),
                'tranchee_le' => $a->decided_at?->toIso8601String(),
            ])->all(),
            'decisions_de_moderation_me_concernant' => $this->moderationEntries($user)->map(fn (ModerationLogEntry $e) => [
                'journal_id' => $e->id,
                'type' => $e->target_type,
                'contenu_id' => $e->target_id,
                'action' => $e->action->value,
                'motif' => $e->motive?->value,
                'date' => $e->created_at->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * @return Collection<int, ModerationLogEntry>
     */
    private function moderationEntries(User $user): Collection
    {
        return ModerationLogEntry::query()
            ->where(function ($q) use ($user): void {
                $q->where(fn ($q) => $q->where('target_type', 'proposal')->whereIn('target_id', Proposal::query()->where('author_id', $user->id)->select('id')))
                    ->orWhere(fn ($q) => $q->where('target_type', 'argument')->whereIn('target_id', Argument::query()->where('author_id', $user->id)->select('id')))
                    ->orWhere(fn ($q) => $q->where('target_type', 'user')->where('target_id', $user->id));
            })
            ->orderBy('id')
            ->get();
    }
}
