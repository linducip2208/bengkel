<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QC verdict for a completed work package.
 *
 * @property int $id
 * @property int $service_work_package_id
 * @property int|null $service_work_task_id
 * @property string $result
 * @property string|null $notes
 * @property int $checked_by
 * @property CarbonInterface $checked_at
 */
#[Fillable(['service_work_package_id', 'service_work_task_id', 'result', 'notes', 'checked_by', 'checked_at'])]
class ServiceWorkQcCheck extends Model
{
    public const RESULT_PASSED = 'passed';

    public const RESULT_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServiceWorkPackage::class, 'service_work_package_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ServiceWorkTask::class, 'service_work_task_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
