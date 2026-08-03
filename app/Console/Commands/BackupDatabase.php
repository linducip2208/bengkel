<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:db {--keep=14 : keep last N backups}';
    protected $description = 'Backup database ke storage/app/backups/ (rotate keep N hari)';

    public function handle(): int
    {
        $disk = config('database.default');
        $conn = config("database.connections.{$disk}");
        $filename = 'backup_' . now()->format('Y-m-d_His') . '.sql';
        $localPath = storage_path('app/backups/' . $filename);
        @mkdir(dirname($localPath), 0755, true);

        if ($conn['driver'] === 'mysql') {
            $cmd = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
                escapeshellarg($conn['host']),
                escapeshellarg($conn['port'] ?? 3306),
                escapeshellarg($conn['username']),
                escapeshellarg($conn['password']),
                escapeshellarg($conn['database']),
                escapeshellarg($localPath)
            );
            exec($cmd, $out, $exit);
            if ($exit !== 0) {
                $this->error('mysqldump failed: ' . implode("\n", $out));
                return self::FAILURE;
            }
        } elseif ($conn['driver'] === 'sqlite') {
            $sourceDb = $conn['database'];
            if (!is_file($sourceDb)) {
                $this->error('SQLite source file not found: ' . $sourceDb);
                return self::FAILURE;
            }
            copy($sourceDb, $localPath);
        } else {
            $this->error("Unsupported driver: {$conn['driver']}");
            return self::FAILURE;
        }

        $size = filesize($localPath);
        $this->info("Backup OK: {$filename} (" . number_format($size / 1024, 2) . " KB)");

        $this->rotate((int) $this->option('keep'));
        return self::SUCCESS;
    }

    private function rotate(int $keep): void
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) return;
        $files = glob($dir . '/backup_*.sql');
        if (!$files) return;
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
        foreach (array_slice($files, $keep) as $oldFile) {
            @unlink($oldFile);
            $this->line('Removed old: ' . basename($oldFile));
        }
    }
}
