<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Birthday greeting tiap pagi jam 09:00
Schedule::command('birthday:send')->dailyAt('09:00');

// Backup database otomatis tiap jam 02:00 dini hari
Schedule::command('backup:db --keep=14')
    ->dailyAt('02:00')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Daily backup failed at ' . now());
    });

// Auto-expire loyalty points older than 1 year (run weekly)
Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('loyalty_transactions')
        ->where('type', 'earn')
        ->where('created_at', '<', now()->subYear())
        ->whereNotIn('id', function ($q) {
            $q->select('reference_id')->from('loyalty_transactions')->where('type', 'expire');
        })
        ->orderBy('id')
        ->chunkById(200, function ($rows) {
            foreach ($rows as $tx) {
                \App\Models\LoyaltyTransaction::create([
                    'customer_id' => $tx->customer_id,
                    'reference_type' => \App\Models\LoyaltyTransaction::class,
                    'reference_id' => $tx->id,
                    'points' => -$tx->points,
                    'type' => 'expire',
                    'description' => 'Auto-expire poin >1 tahun (dari tx #' . $tx->id . ')',
                ]);
                \App\Models\Customer::where('id', $tx->customer_id)->decrement('loyalty_points', $tx->points);
            }
        });
})->weekly();

// IndexNow auto-submit: setiap hari jam 02:45
Schedule::command('seo:indexnow --new')
    ->dailyAt('02:45')
    ->appendOutputTo(storage_path('logs/indexnow.log'));
