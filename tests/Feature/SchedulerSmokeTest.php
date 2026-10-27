<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * Kunci scheduler terdaftar (item audit: scheduler tanpa test).
 * Menjaga perintah kritis (backup DB, birthday) tidak terhapus diam-diam
 * saat routes/console.php disunting.
 */
class SchedulerSmokeTest extends TestCase
{
    public function test_critical_jobs_are_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->map(fn ($e) => [$e->getSummaryForDisplay(), $e->expression]);

        // Backup DB otomatis tiap jam 02:00.
        $this->assertTrue(
            $events->contains(fn ($pair) => str_contains($pair[0], 'backup:db') && $pair[1] === '0 2 * * *'),
            'backup:db harian 02:00 hilang dari scheduler'
        );

        // Birthday greeting tiap pagi jam 09:00.
        $this->assertTrue(
            $events->contains(fn ($pair) => str_contains($pair[0], 'birthday:send') && $pair[1] === '0 9 * * *'),
            'birthday:send harian 09:00 hilang dari scheduler'
        );

        // Sanity: scheduler tidak boleh kosong/melenceng jauh dari baseline 13 job.
        $this->assertGreaterThanOrEqual(10, $events->count());
    }
}
