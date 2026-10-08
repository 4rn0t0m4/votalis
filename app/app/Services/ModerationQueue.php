<?php

namespace App\Services;

use App\Enums\ModerationAction;
use App\Enums\ReportMotive;
use App\Enums\ReportStatus;
use App\Models\Argument;
use App\Models\ModerationLogEntry;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * File de modération (CDC section 6) : un dossier par contenu signalé, trié par gravité
 * du motif le plus grave puis par ancienneté du premier signalement.
 */
class ModerationQueue
{
    /**
     * @return Collection<int, array{target: Proposal|Argument, severity: int, count: int, oldest: Carbon}>
     */
    public function cases(int $limit = 50): Collection
    {
        $rows = DB::table('reports')
            ->select('target_type', 'target_id')
            ->selectRaw('COUNT(*) AS report_count')
            ->selectRaw('MIN(created_at) AS oldest')
            ->selectRaw('ARRAY_TO_STRING(ARRAY_AGG(DISTINCT motive), \',\') AS motives')
            ->where('status', ReportStatus::Open->value)
            ->groupBy('target_type', 'target_id')
            ->get();

        // Gravité du motif le plus grave, calculée côté PHP depuis l'énumération.
        $rows = $rows->map(function (object $row): object {
            $row->severity = max(array_map(fn (string $m) => ReportMotive::from($m)->severity(), explode(',', (string) $row->motives)));

            return $row;
        })->sortBy([['severity', 'desc'], ['oldest', 'asc']])->take($limit)->values();

        $proposals = Proposal::query()->with(['theme', 'author'])->findMany($rows->where('target_type', 'proposal')->pluck('target_id'))->keyBy('id');
        $arguments = Argument::query()->with(['proposal', 'author'])->findMany($rows->where('target_type', 'argument')->pluck('target_id'))->keyBy('id');

        return $rows->map(function (object $row) use ($proposals, $arguments): ?array {
            $target = $row->target_type === 'proposal' ? $proposals->get($row->target_id) : $arguments->get($row->target_id);

            if ($target === null) {
                return null;
            }

            return [
                'target' => $target,
                'severity' => (int) $row->severity,
                'count' => (int) $row->report_count,
                'oldest' => Carbon::parse($row->oldest),
            ];
        })->filter()->values();
    }

    public function openCount(): int
    {
        return (int) DB::table('reports')->where('status', ReportStatus::Open->value)->distinct()->count(DB::raw('(target_type, target_id)'));
    }

    /**
     * Contexte sur l'auteur, pseudonymisé : ancienneté du compte et décisions antérieures
     * ayant masqué ou renvoyé l'un de ses contenus.
     *
     * @return array{pseudonym: string, account_age_days: int|null, prior_sanctions: int}
     */
    public function authorContext(Proposal|Argument $target): array
    {
        $author = $target->author;

        if (! $author instanceof User) {
            return ['pseudonym' => $target->authorName(), 'account_age_days' => null, 'prior_sanctions' => 0];
        }

        $sanctions = ModerationLogEntry::query()
            ->whereIn('action', [ModerationAction::Hide->value, ModerationAction::RequestRewrite->value])
            ->where(function ($q) use ($author): void {
                $q->where(fn ($q) => $q->where('target_type', 'proposal')->whereIn('target_id', Proposal::query()->where('author_id', $author->id)->select('id')))
                    ->orWhere(fn ($q) => $q->where('target_type', 'argument')->whereIn('target_id', Argument::query()->where('author_id', $author->id)->select('id')));
            })
            ->count();

        return [
            'pseudonym' => $author->pseudonym,
            'account_age_days' => (int) $author->created_at?->diffInDays(now()),
            'prior_sanctions' => $sanctions,
        ];
    }

    /**
     * Historique agrégé d'un signaleur, sans son identité : signalements émis et part retenue
     * (décision de masquage ou de reformulation parmi les signalements traités).
     *
     * @return array{emitted: int, handled: int, upheld: int}
     */
    public function reporterHistory(Report $report): array
    {
        if ($report->reporter_id === null) {
            return ['emitted' => 0, 'handled' => 0, 'upheld' => 0];
        }

        $handled = Report::query()
            ->where('reporter_id', $report->reporter_id)
            ->where('status', ReportStatus::Handled)
            ->whereKeyNot($report->id)
            ->with('logEntry')
            ->get();

        return [
            'emitted' => Report::query()->where('reporter_id', $report->reporter_id)->count(),
            'handled' => $handled->count(),
            'upheld' => $handled->filter(fn (Report $r) => in_array($r->logEntry?->action, [ModerationAction::Hide, ModerationAction::RequestRewrite], true))->count(),
        ];
    }
}
