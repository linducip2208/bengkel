<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $service) {}

    public function index(): View
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $emailSettings = $settings;
        $invoiceSettings = $settings;
        $whatsappSettings = $settings;
        $notificationSettings = $settings;
        return view('settings.index', compact('settings', 'emailSettings', 'invoiceSettings', 'whatsappSettings', 'notificationSettings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->except(['_token', '_method']);

        // Handle nested settings[xxx] array
        if (isset($data['settings']) && is_array($data['settings'])) {
            foreach ($data['settings'] as $key => $value) {
                $this->service->set($key, $value);
            }
            unset($data['settings']);
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            $this->service->set('company_logo', $path);
        }

        // Handle remaining flat fields
        foreach ($data as $key => $value) {
            if (!in_array($key, ['_token', '_method'])) {
                $this->service->set($key, $value);
            }
        }

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function backupPage(): View { return view('settings.backup'); }

    public function backup()
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $db = config('database.connections.mysql');
        $filename = 'backup-' . now()->format('Ymd-His') . '.sql';
        $path = $dir . '/' . $filename;
        $cmd = sprintf('mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s',
            escapeshellarg($db['username']), escapeshellarg($db['password']),
            escapeshellarg($db['host']), escapeshellarg($db['port']),
            escapeshellarg($db['database']), escapeshellarg($path));
        exec($cmd, output: $output, result_code: $exitCode);
        if ($exitCode === 0) return response()->download($path)->deleteFileAfterSend();
        return back()->with('error', 'Backup gagal.');
    }

    public function backupDownload(Request $request)
    {
        $file = storage_path('app/backups/' . basename($request->file));
        return file_exists($file) ? response()->download($file) : back()->with('error', 'File tidak ditemukan.');
    }

    public function cacheClear() { \Illuminate\Support\Facades\Artisan::call('optimize:clear'); return back()->with('success', 'Cache cleared.'); }
    public function optimize() { \Illuminate\Support\Facades\Artisan::call('optimize'); return back()->with('success', 'Optimized.'); }
}
