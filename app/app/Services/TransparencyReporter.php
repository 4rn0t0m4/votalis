<?php

namespace App\Services;

use App\Enums\AppealStatus;
use App\Enums\ModerationAction;
use App\Enums\ReportMotive;
use App\Enums\SignalStatus;
use App\Models\TransparencyReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rapport de transparence (CDC section 6) : volumes par motif, décisions, contestations et
 * leur issue, comptes suspendus, opérations coordonnées confirmées. Agrégats seulement.
 */
class TransparencyReporter
{
    public function generate(Carbon $start, Carbon $end): TransparencyReport
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $data = [
            'reports' => [
                'total' => DB::table('reports')->whereBetween('created_at', [$start, $end])->count(),
                'by_motive' => $this->countBy('reports', 'motive', $start, $end, array_map(fn (ReportMotive $m) => $m->value, ReportMotive::cases())),
            ],
            'decisions' => [
                'total' => DB::table('moderation_log')->whereBetween('created_at', [$start, $end])->count(),
                'by_action' => $this->countBy('moderation_log', 'action', $start, $end, array_map(fn (ModerationAction $a) => $a->value, ModerationAction::cases())),
                'by_motive' => $this->countBy('moderation_log', 'motive', $start, $end, array_map(fn (ReportMotive $m) => $m->value, ReportMotive::cases())),
            ],
            'appeals' => [
                'filed' => DB::table('appeals')->whereBetween('created_at', [$start, $end])->count(),
                'confirmed' => DB::table('appeals')->whereBetween('decided_at', [$start, $end])->where('status', AppealStatus::Confirmed->value)->count(),
                'overturned' => DB::table('appeals')->whereBetween('decided_at', [$start, $end])->where('status', AppealStatus::Overturned->value)->count(),
            ],
            'suspensions' => DB::table('moderation_log')->whereBetween('created_at', [$start, $end])->where('action', ModerationAction::Suspend->value)->count(),
            'coordinated_operations' => DB::table('integrity_signals')->whereBetween('reviewed_at', [$start, $end])->where('status', SignalStatus::Confirmed->value)->count(),
            'signals_raised' => DB::table('integrity_signals')->whereBetween('created_at', [$start, $end])->count(),
        ];

        return TransparencyReport::query()->updateOrCreate(
            ['period_start' => $start->toDateString(), 'period_end' => $end->toDateString()],
            ['data' => $data, 'generated_at' => now()],
        );
    }

    /** Trimestre civil précédant la date donnée. */
    public function previousQuarter(?Carbon $reference = null): TransparencyReport
    {
        $reference ??= now();
        $start = $reference->copy()->subQuarterNoOverflow()->startOfQuarter();

        return $this->generate($start, $start->copy()->endOfQuarter());
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, int>
     */
    private function countBy(string $table, string $column, Carbon $start, Carbon $end, array $keys): array
    {
        $rows = DB::table($table)
            ->select($column)
            ->selectRaw('COUNT(*) AS n')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull($column)
            ->groupBy($column)
            ->pluck('n', $column);

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = (int) ($rows[$key] ?? 0);
        }

        return $result;
    }
}
