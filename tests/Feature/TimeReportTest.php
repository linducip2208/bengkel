<?php

namespace Tests\Feature;

use App\Models\ServiceWorkTask;
use App\Models\ServiceWorkTimeEntry;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\ReportService;
use App\Services\WorkshopFlowService;
use Illuminate\Support\Facades\DB;

/**
 * Laporan waktu teknisi/paket: angka benar + tanpa N+1 time-entries
 * (batas query diikat agar regresi ketahuan).
 */
class TimeReportTest extends WorkshopFlowTestCase
{
    private function makeCompletedTaskWithTime(string $techName, int $standardMinutes, int $workedMinutes): ServiceWorkTask
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'PAKET '.$techName,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $tech = User::factory()->create(['name' => $techName, 'is_active' => true]);
        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();
        $task->update([
            'assigned_to' => $tech->id,
            'status' => ServiceWorkTask::STATUS_COMPLETED,
            'standard_minutes' => $standardMinutes,
        ]);

        ServiceWorkTimeEntry::create([
            'service_work_task_id' => $task->id,
            'user_id' => $tech->id,
            'started_at' => now()->subMinutes($workedMinutes),
            'ended_at' => now(),
        ]);

        return $task->fresh();
    }

    private function countQueries(callable $fn): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });
        $fn();
        DB::flushQueryLog();

        return $count;
    }

    public function test_technician_report_totals_and_bounded_queries(): void
    {
        $this->makeCompletedTaskWithTime('Teknisi A', 30, 60);
        $this->makeCompletedTaskWithTime('Teknisi A2', 30, 30);
        $this->makeCompletedTaskWithTime('Teknisi B', 45, 45);
        $this->makeCompletedTaskWithTime('Teknisi B2', 15, 20);

        $report = null;
        $queries = $this->countQueries(function () use (&$report): void {
            $report = app(ReportService::class)->technicianTimeReport();
        });

        $byName = collect($report['rows'])->keyBy('technician_name');
        $this->assertSame(1, $byName['Teknisi A']['completed_tasks']);
        $this->assertSame(30, $byName['Teknisi A']['standard_minutes']);
        $this->assertSame(60, $byName['Teknisi A']['actual_minutes']);
        $this->assertSame(45, $byName['Teknisi B']['actual_minutes']);

        // Tetap 3 query (tasks + assignee + timeEntries); tanpa fix = 2 + N.
        $this->assertLessThanOrEqual(4, $queries);
    }

    public function test_package_report_actuals_and_bounded_queries(): void
    {
        $this->makeCompletedTaskWithTime('Teknisi C', 30, 90);
        $this->makeCompletedTaskWithTime('Teknisi D', 20, 25);
        $this->makeCompletedTaskWithTime('Teknisi E', 10, 10);

        $report = null;
        $queries = $this->countQueries(function () use (&$report): void {
            $report = app(ReportService::class)->workPackageTimeReport();
        });

        $row = collect($report['rows'])->firstWhere('title', 'PAKET Teknisi C');
        $this->assertNotNull($row);
        $this->assertSame(90, $row['actual_minutes']);
        $this->assertSame(60, $row['variance_minutes']);

        // Tetap 4 query (finding null → eager di-skip); tanpa fix = 3 + N.
        $this->assertLessThanOrEqual(5, $queries);
    }
}
