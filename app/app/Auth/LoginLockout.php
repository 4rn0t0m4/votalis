<?php

namespace App\Auth;

use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Verrouillage progressif après échecs de connexion : au-delà du nombre d'échecs
 * configuré, le couple identifiant + IP est bloqué 1 min, puis 5, puis 15.
 */
class LoginLockout
{
    public function __construct(private readonly Cache $cache) {}

    public static function key(string $identifier, string $ip): string
    {
        return hash('sha256', mb_strtolower(trim($identifier)).'|'.$ip);
    }

    /** Secondes restantes de verrouillage, 0 si libre. */
    public function remainingSeconds(string $key): int
    {
        $until = (int) $this->cache->get("lockout:until:{$key}", 0);

        return max(0, $until - time());
    }

    public function recordFailure(string $key): void
    {
        $window = (int) config('votalis.lockout.window', 3600);
        $failures = (int) $this->cache->get("lockout:failures:{$key}", 0) + 1;
        $this->cache->put("lockout:failures:{$key}", $failures, $window);

        $threshold = (int) config('votalis.lockout.failures', 5);

        if ($failures < $threshold) {
            return;
        }

        /** @var list<int> $durations */
        $durations = config('votalis.lockout.durations', [60, 300, 900]);
        $level = min((int) $this->cache->get("lockout:level:{$key}", 0), count($durations) - 1);
        $duration = $durations[$level];

        $this->cache->put("lockout:until:{$key}", time() + $duration, $window);
        $this->cache->put("lockout:level:{$key}", $level + 1, $window);
        $this->cache->forget("lockout:failures:{$key}");
    }

    public function clear(string $key): void
    {
        $this->cache->forget("lockout:failures:{$key}");
        $this->cache->forget("lockout:until:{$key}");
        $this->cache->forget("lockout:level:{$key}");
    }
}
