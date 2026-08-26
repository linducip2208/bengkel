<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:db {--keep=14 : Keep last N days}';

    protected $description = 'Backup database to SQL file';

    public function handle()
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'backup-'.now()->format('Ymd-His').'.sql';
        $path = $dir.'/'.$filename;

        $db = config('database.connections.mysql');
        $cmd = sprintf('mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s',
            escapeshellarg($db['username']), escapeshellarg($db['password']),
            escapeshellarg($db['host']), escapeshellarg($db['port']),
            escapeshellarg($db['database']), escapeshellarg($path)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode === 0) {
            $this->info("Backup created: {$filename}");

            // Clean old backups
            $keep = (int) $this->option('keep');
            $files = glob($dir.'/backup-*.sql');
            rsort($files);
            foreach (array_slice($files, $keep) as $old) {
                unlink($old);
                $this->line('Deleted old: '.basename($old));
            }
        } else {
            $this->error("Backup failed with exit code {$exitCode}");
        }
    }
}
