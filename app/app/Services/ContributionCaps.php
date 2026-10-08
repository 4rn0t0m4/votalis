<?php

namespace App\Services;

use App\Models\Theme;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Plafonds de contribution (CDC 4.7), appliqués côté serveur. Les comptes récents
 * ont des plafonds divisés par deux. Les valeurs vivent dans config/votalis.php.
 */
class ContributionCaps
{
    public function proposalsPerMonthPerTheme(User $user): int
    {
        return $this->adjust($user, (int) config('votalis.caps.proposals_per_month_per_theme', 3));
    }

    public function argumentsPerDay(User $user): int
    {
        return $this->adjust($user, (int) config('votalis.caps.arguments_per_day', 20));
    }

    public function votesPerDay(User $user): int
    {
        return $this->adjust($user, (int) config('votalis.caps.votes_per_day', 300));
    }

    /** Seuls les nouveaux votes comptent ; réviser un vote existant est toujours possible. */
    public function remainingVotes(User $user): int
    {
        $used = $user->votes()->where('created_at', '>=', now()->subDay())->count();

        return max(0, $this->votesPerDay($user) - $used);
    }

    /**
     * @throws ValidationException
     */
    public function assertCanVote(User $user): void
    {
        if ($this->remainingVotes($user) === 0) {
            throw ValidationException::withMessages([
                'cap' => [__('Vous avez atteint le plafond de :max votes par jour. Revenez demain, vos votes existants restent modifiables.', ['max' => $this->votesPerDay($user)])],
            ]);
        }
    }

    public function remainingProposals(User $user, Theme $theme): int
    {
        $used = $user->proposals()->where('theme_id', $theme->id)->where('created_at', '>=', now()->subMonth())->count();

        return max(0, $this->proposalsPerMonthPerTheme($user) - $used);
    }

    public function remainingArguments(User $user): int
    {
        $used = $user->arguments()->where('created_at', '>=', now()->subDay())->count();

        return max(0, $this->argumentsPerDay($user) - $used);
    }

    /**
     * @throws ValidationException
     */
    public function assertCanCreateProposal(User $user, Theme $theme): void
    {
        if ($this->remainingProposals($user, $theme) === 0) {
            throw ValidationException::withMessages([
                'cap' => [__('Vous avez atteint le plafond de :max propositions par mois dans ce thème. Vous pourrez en déposer une nouvelle plus tard.', ['max' => $this->proposalsPerMonthPerTheme($user)])],
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function assertCanCreateArgument(User $user): void
    {
        if ($this->remainingArguments($user) === 0) {
            throw ValidationException::withMessages([
                'cap' => [__('Vous avez atteint le plafond de :max arguments par jour.', ['max' => $this->argumentsPerDay($user)])],
            ]);
        }
    }

    private function adjust(User $user, int $cap): int
    {
        $newAccountDays = (int) config('votalis.caps.new_account_days', 7);

        if ($user->created_at !== null && $user->created_at->gt(now()->subDays($newAccountDays))) {
            return max(1, intdiv($cap, 2));
        }

        return $cap;
    }
}
