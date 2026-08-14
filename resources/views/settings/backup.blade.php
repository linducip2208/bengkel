@extends('layouts.app')
@section('title', 'Backup & Restore')
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card mb-3"><div class="card-header"><strong><i class="fas fa-download me-2"></i>Backup Database</strong></div><div class="card-body">
            <form method="POST" action="{{ route('settings.backup') }}">
                @csrf
                <button class="btn btn-primary"><i class="fas fa-database me-1"></i>Download Backup SQL</button>
            </form>
            <small class="text-muted mt-2 d-block">Backup otomatis terjadwal tiap jam 02:00 WIB (keep 14 hari).</small>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3"><div class="card-header"><strong><i class="fas fa-cloud-upload-alt me-2"></i>Sistem</strong></div><div class="card-body">
            <form method="POST" action="{{ route('settings.cache-clear') }}" class="d-inline">@csrf<button class="btn btn-warning"><i class="fas fa-broom me-1"></i>Clear Cache</button></form>
            <form method="POST" action="{{ route('settings.optimize') }}" class="d-inline">@csrf<button class="btn btn-info"><i class="fas fa-rocket me-1"></i>Optimize</button></form>
        </div></div>
    </div>
</div>

@php $files = glob(storage_path('app/backups/*.sql')); rsort($files); @endphp
<div class="card"><div class="card-header"><strong>Riwayat Backup</strong></div><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>File</th><th>Ukuran</th><th>Tanggal</th><th></th></tr></thead><tbody>
    @forelse(array_slice($files, 0, 20) as $f)
    <tr>
        <td><i class="fas fa-file-archive me-2"></i>{{ basename($f) }}</td>
        <td>{{ round(filesize($f)/1024/1024, 2) }} MB</td>
        <td>{{ date('d M Y H:i', filemtime($f)) }}</td>
        <td><a href="{{ route('settings.backup-download', ['file' => basename($f)]) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a></td>
    </tr>
    @empty
    <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada file backup.</td></tr>
    @endforelse
</tbody></table></div></div></div>
@endsection
