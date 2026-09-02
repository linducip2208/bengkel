<?php

namespace Tests\Feature;

use App\Models\ServiceWorkTask;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\ReportService;
use App\Services\WorkshopFlowService;
use Spatie\Permission\Models\Role;

/**
 * §38 — Standard vs actual time reporting per work package and technician.
 */
class WorkPackageTimeReportTest extends WorkshopFlowTestCase
{
    protected function makeExecutedPackage(string $title, int $standardMinutes, int $workMinutes, ?int $techId = null): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => $title, 'standard_minutes' => $standardMinutes,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $task = ServiceWorkTask::where('service_work_package_id', $package->id)->firstOrFail();
        if ($techId !== null) {
            $task->forceFill(['assigned_to' => $techId])->save();
        }

        $flow->startTask($task->fresh());
        $this->travel($workMinutes)->minutes();
        $flow->finishTask($task->fresh());

        return [$package, $task->fresh()];
    }

    public function test_package_report_shows_standard_actual_and_variance(): void
    {
        // 30 min standard, 25 min actual → variance -5.
        [$packageA] = $this->makeExecutedPackage('KERJA CEPAT', 30, 25);
        // 15 min standard, 22 min actual → variance +7.
        [$packageB] = $this->makeExecutedPackage('KERJA LAMBAT', 15, 22);

        $report = app(ReportService::class)->workPackageTimeReport();

        $rows = collect($report['rows']);
        $rowA = $rows->firstWhere('title', 'KERJA CEPAT');
        $rowB = $rows->firstWhere('title', 'KERJA LAMBAT');

        $this->assertNotNull($rowA);
        $this->assertNotNull($rowB);

        $this->assertSame(30, $rowA['standard_minutes']);
        $this->assertSame(25, $rowA['actual_minutes']);
        $this->assertSame(-5, $rowA['variance_minutes']);

        $this->assertSame(15, $rowB['standard_minutes']);
        $this->assertSame(22, $rowB['actual_minutes']);
        $this->assertSame(7, $rowB['variance_minutes']);

        $this->assertSame(45, $report['total_standard_minutes']);
        $this->assertSame(47, $report['total_actual_minutes']);
        $this->assertSame(2, $report['total_variance_minutes']);
        $this->assertNotNull($report['efficiency']);
    }

    public function test_package_without_task_shows_no_actual(): void
    {
        $service = $this->makeService();
        app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'BELUM DIEKSEKUSI', 'standard_minutes' => 20,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);

        $report = app(ReportService::class)->workPackageTimeReport();
        $row = collect($report['rows'])->firstWhere('title', 'BELUM DIEKSEKUSI');

        $this->assertNotNull($row);
        $this->assertSame(20, $row['standard_minutes']);
        $this->assertNull($row['actual_minutes']);
        $this->assertNull($row['variance_minutes']);
    }

    public function test_standard_time_is_not_overwritten_by_actual(): void
    {
        [$package, $task] = $this->makeExecutedPackage('STANDARD STAYS', 30, 10);

        $this->assertSame(30, $task->standard_minutes);
        $this->assertSame(10, $task->actualMinutes());
        $this->assertSame(30, $package->fresh()->standard_minutes);
    }

    public function test_technician_report_aggregates_completed_tasks(): void
    {
        $tech = User::factory()->create(['is_active' => true]);

        // Two completed tasks for tech: 30 std/25 actual + 15 std/22 actual.
        $this->makeExecutedPackage('TASK A', 30, 25, $tech->id);
        $this->makeExecutedPackage('TASK B', 15, 22, $tech->id);

        // One task with no technician — must not appear.
        $this->makeExecutedPackage('UNASSIGNED', 20, 20);

        $report = app(ReportService::class)->technicianTimeReport();
        $rows = collect($report['rows']);

        $this->assertCount(1, $rows);
        $row = $rows->first();

        $this->assertSame($tech->id, $row['technician_id']);
        $this->assertSame($tech->name, $row['technician_name']);
        $this->assertSame(2, $row['total_tasks']);
        $this->assertSame(2, $row['completed_tasks']);
        $this->assertSame(45, $row['standard_minutes']);
        $this->assertSame(47, $row['actual_minutes']);
    }

    public function test_report_page_renders_for_authorized_user(): void
    {
        $this->makeExecutedPackage('RENDER ME', 30, 25);

        $this->get(route('reports.work-time'))
            ->assertOk()
            ->assertSee('RENDER ME')
            ->assertSee('Standard vs Actual')
            ->assertSee('Per Teknisi');
    }

    public function test_report_page_requires_permission(): void
    {
        Role::findOrCreate('tanpa_report', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('tanpa_report');
        $this->actingAs($user);

        $this->get(route('reports.work-time'))->assertForbidden();
    }
}
