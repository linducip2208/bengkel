<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One timed work interval. Duration is computed server-side from
 * started_at/ended_at — the frontend never submits authoritative time.
 *
 * @property int $id
 * @property int $service_work_task_id
 * @property int $user_id
 * @property CarbonInterface $started_at
 * @property CarbonInterface|null $ended_at
 * @property int $duration_seconds
 */
#[Fillable(['service_work_task_id', 'user_id', 'started_at', 'ended_at', 'duration_seconds'])]
class ServiceWorkTimeEntry extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ServiceWorkTask::class, 'service_work_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    /** Seconds so far (open entry counts up to now). */
    public function effectiveSeconds(): int
    {
        $end = $this->ended_at ?? now();

        return max(0, $end->diffInSeconds($this->started_at, false) * -1);
    }
}
