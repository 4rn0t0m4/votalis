<?php

namespace App\Services;

use App\Enums\TradeoffStatus;
use App\Models\Proposal;
use App\Models\Tradeoff;
use App\Models\TradeoffAnswer;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\Cache;

/**
 * « Le pouls de la plateforme » : agrégats publics affichés sur l'accueil.
 * Uniquement des comptages (jamais un classement, jamais une donnée individuelle), en cache court.
 */
class PlatformPulse
{
    public const CACHE_SECONDS = 60;

    /**
     * @return array{proposals: int, votes: int, answers: int, participants: int}
     */
    public function counts(): array
    {
        /** @var array{proposals: int, votes: int, answers: int, participants: int} $counts */
        $counts = Cache::remember('pulse:counts', now()->addSeconds(self::CACHE_SECONDS), fn (): array => [
            'proposals' => Proposal::query()->published()->count(),
            'votes' => Vote::query()->count(),
            'answers' => TradeoffAnswer::query()->count(),
            'participants' => User::query()->whereNotNull('email_verified_at')->count(),
        ]);

        return $counts;
    }

    /** Arbitrage ouvert mis en avant : le plus récent. */
    public function featuredTradeoff(): ?Tradeoff
    {
        return Tradeoff::query()
            ->where('status', TradeoffStatus::Open)
            ->withCount(['items', 'answers'])
            ->latest()
            ->first();
    }

    public static function flush(): void
    {
        Cache::forget('pulse:counts');
    }
}
