<?php

namespace App\Console\Commands;

use App\Models\NotificationQueue;
use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProcessNotificationQueue extends Command
{
    protected $signature = 'notifications:process {--limit=50}';

    protected $description = 'Process pending notifications from queue';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $items = NotificationQueue::where('status', 'pending')->limit($limit)->get();

        foreach ($items as $item) {
            try {
                if ($item->channel === 'whatsapp') {
                    $wa = app(WhatsAppNotificationService::class);
                    $result = $wa->send($item->recipient, $item->message);
                } elseif ($item->channel === 'email') {
                    Mail::raw($item->message, function ($msg) use ($item) {
                        $msg->to($item->recipient)->subject('Aplikasi Bengkel Terbaik Notification');
                    });
                    $result = ['success' => true];
                } else {
                    $item->update(['status' => 'failed', 'error' => 'Unknown channel']);

                    continue;
                }

                $item->update([
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'error' => $result['error'] ?? null,
                    'sent_at' => $result['success'] ? now() : null,
                ]);

                $this->info("{$item->channel} notification to {$item->recipient}: ".($result['success'] ? 'OK' : 'FAIL'));
            } catch (\Throwable $e) {
                $item->update(['status' => 'failed', 'error' => $e->getMessage()]);
                $this->error("{$item->channel} to {$item->recipient}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$items->count()} notifications.");
    }
}
