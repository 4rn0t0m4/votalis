<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Date de dernière visite connectée, au jour près, écrite au plus une fois par jour.
 * Sert uniquement à la purge des comptes inactifs (CDC section 8). Ni heure, ni IP, ni page.
 */
class TrackLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->last_seen_at?->isToday() !== true) {
            DB::table('users')->where('id', $user->id)->update([
                'last_seen_at' => today()->toDateString(),
                'inactivity_notice_sent_at' => null,
            ]);
            $user->last_seen_at = today();
            $user->inactivity_notice_sent_at = null;
        }

        return $next($request);
    }
}
