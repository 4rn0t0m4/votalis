<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\InactivityNotice;
use App\Services\AccountEraser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comptes inactifs (CDC section 8) : préavis `notice_days` jours avant l'échéance de
 * `inactive_months` mois sans visite connectée, puis suppression par AccountEraser.
 * Les rôles privilégiés ne sont jamais purgés automatiquement.
 */
class PurgeInactiveAccounts extends Command
{
    protected $signature = 'accounts:purge-inactive {--dry-run : Affiche les comptes concernés sans rien envoyer ni supprimer}';

    protected $description = 'Prévient puis supprime les comptes participants inactifs depuis la durée de conservation';

    public function handle(AccountEraser $eraser): int
    {
        $months = (int) config('votalis.retention.inactive_months', 36);
        $noticeDays = (int) config('votalis.retention.notice_days', 30);
        $dryRun = (bool) $this->option('dry-run');

        $deadline = now()->subMonths($months)->toDateString();
        $noticeThreshold = now()->subMonths($months)->addDays($noticeDays)->toDateString();
        $activity = DB::raw('COALESCE(last_seen_at, created_at::date)');

        // 1. Préavis : inactifs depuis (durée − préavis), pas encore prévenus.
        $toNotify = User::query()
            ->where('role', Role::Participant->value)
            ->whereNull('inactivity_notice_sent_at')
            ->where($activity, '<', $noticeThreshold)
            ->get();

        foreach ($toNotify as $user) {
            if (! $dryRun) {
                $user->notify(new InactivityNotice($noticeDays));
                $user->forceFill(['inactivity_notice_sent_at' => now()])->save();
            }
        }

        // 2. Suppression : inactifs depuis la durée complète, prévenus depuis au moins `notice_days` jours.
        $toErase = User::query()
            ->where('role', Role::Participant->value)
            ->whereNotNull('inactivity_notice_sent_at')
            ->where('inactivity_notice_sent_at', '<=', now()->subDays($noticeDays))
            ->where($activity, '<', $deadline)
            ->get();

        foreach ($toErase as $user) {
            if (! $dryRun) {
                $eraser->erase($user, 'inactivity');
            }
        }

        $this->info(sprintf('%d préavis, %d suppression(s)%s.', $toNotify->count(), $toErase->count(), $dryRun ? ' (simulation)' : ''));

        return self::SUCCESS;
    }
}
