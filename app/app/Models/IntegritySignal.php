<?php

namespace App\Models;

use App\Enums\SignalStatus;
use App\Enums\SignalType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Signal d'intégrité calculé par `integrity:scan`. Ne porte que des identifiants internes.
 *
 * @property int $id
 * @property SignalType $type
 * @property int $severity
 * @property array<string, mixed> $targets
 * @property array<string, mixed>|null $details
 * @property SignalStatus $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon $window_date
 * @property Carbon|null $created_at
 */
#[Fillable(['type', 'severity', 'targets', 'details', 'status', 'reviewed_by', 'reviewed_at', 'window_date'])]
class IntegritySignal extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SignalType::class,
            'status' => SignalStatus::class,
            'targets' => 'array',
            'details' => 'array',
            'reviewed_at' => 'datetime',
            'window_date' => 'date',
        ];
    }
}
