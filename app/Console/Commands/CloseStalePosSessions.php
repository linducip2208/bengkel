<?php

namespace App\Console\Commands;

use App\Models\PosSession;
use Illuminate\Console\Command;

class CloseStalePosSessions extends Command
{
    protected $signature = 'pos:close-stale-sessions {--hours=12 : Sesi terbuka lebih dari N jam akan ditutup otomatis}';
    protected $description = 'Auto-close POS sessions that were left open (kasir lupa tutup / browser ditutup tanpa tutup sesi)';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $threshold = now()->subHours($hours);

        $stale = PosSession::withoutGlobalScopes()
            ->where('status', 'open')
            ->where('opened_at', '<', $threshold)
            ->get();

        if ($stale->isEmpty()) {
            $this->info("Tidak ada sesi POS stale (terbuka > {$hours} jam).");
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($stale as $session) {
            $revenue = $session->revenue;
            $expected = $session->opening_balance + $revenue;

            $session->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closing_balance' => $expected,
                'expected_balance' => $expected,
                'difference' => 0,
                'notes' => trim(($session->notes ? $session->notes . "\n" : '') . "Auto-closed: sesi menggantung > {$hours} jam (kasir lupa tutup)."),
            ]);
            $count++;
            $this->line("  Ditutup otomatis: Sesi #{$session->id} ({$session->user?->name}) — revenue {$revenue}");
        }

        $this->info("Selesai. {$count} sesi POS ditutup otomatis.");
        return self::SUCCESS;
    }
}
