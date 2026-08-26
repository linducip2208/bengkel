<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBirthdayGreetings extends Command
{
    protected $signature = 'birthday:send {--dry : tampil saja tanpa kirim}';

    protected $description = 'Kirim ucapan ulang tahun ke customer + tawaran diskon';

    public function handle(NotificationService $notif): int
    {
        $today = now();
        $customers = Customer::withoutGlobalScopes()
            ->whereNotNull('birthday')
            ->whereRaw('MONTH(birthday) = ?', [$today->month])
            ->whereRaw('DAY(birthday) = ?', [$today->day])
            ->get();

        if ($customers->isEmpty()) {
            $this->info('Tidak ada customer ulang tahun hari ini.');

            return self::SUCCESS;
        }

        $appName = config('app.name', config('app.name'));
        foreach ($customers as $c) {
            $msg = "Halo {$c->name}, selamat ulang tahun! 🎂\n\nKhusus untukmu hari ini & 7 hari ke depan, dapatkan DISKON 10% untuk service apa saja di {$appName}. Tunjukkan pesan ini saat datang.\n\nSemoga sehat selalu!\n\n— Tim {$appName}";
            $this->line("→ {$c->name} ({$c->phone})");

            if ($this->option('dry')) {
                continue;
            }

            // Coba email kalau ada
            if ($c->email) {
                try {
                    Mail::raw($msg, function ($m) use ($c, $appName) {
                        $m->to($c->email)->subject("🎂 Selamat Ulang Tahun dari {$appName}!");
                    });
                    EmailLog::create(['to' => $c->email, 'subject' => 'Birthday greeting', 'body' => $msg, 'status' => 'sent']);
                } catch (\Throwable $e) {
                    EmailLog::create(['to' => $c->email, 'subject' => 'Birthday greeting', 'body' => $msg, 'status' => 'failed', 'error_message' => $e->getMessage()]);
                }
            }

            ActivityLog::record('birthday.greet', $c, "Birthday greeting sent to {$c->name}");
        }

        $this->info("Kirim greeting ke {$customers->count()} customer.");

        return self::SUCCESS;
    }
}
