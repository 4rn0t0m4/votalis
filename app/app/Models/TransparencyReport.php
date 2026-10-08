<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Rapport de transparence d'une période : agrégats seulement, public.
 *
 * @property int $id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property array<string, mixed> $data
 * @property Carbon $generated_at
 */
#[Fillable(['period_start', 'period_end', 'data', 'generated_at'])]
class TransparencyReport extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'data' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function title(): string
    {
        return 'Du '.$this->period_start->translatedFormat('j F Y').' au '.$this->period_end->translatedFormat('j F Y');
    }
}
