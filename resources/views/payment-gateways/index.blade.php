@extends('layouts.app')
@section('title', 'Payment Gateway')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-credit-card me-2"></i>Payment Gateway</h4>
    <a href="{{ route('payment-gateways.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Gateway</a>
</div>

<div class="alert alert-info">
    <h6 class="mb-2"><i class="bi bi-info-circle me-1"></i>Apa itu Payment Gateway di sini?</h6>
    <p class="mb-1"><strong>Untuk: customer bayar invoice online</strong> (bukan untuk SaaS subscription). Owner/admin input config sendiri PG mereka — bukan hardcode.</p>
    <p class="mb-0 small">
        Pakai format-based adapter: <code>redirect</code> (Midtrans Snap, Doku, dst), <code>embed</code> (Midtrans Core, Xendit), <code>qr</code> (QRIS dinamis), atau <code>manual_transfer</code> (transfer manual). Owner pilih format sesuai PG yang dibeli, lalu isi API key sendiri.
    </p>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card"><div class="card-body">
    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Nama</th><th>Format</th><th>Base URL</th><th>Mode</th><th>Default</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($gateways as $g)
            <tr>
                <td><strong>{{ $g->name }}</strong><br><small class="text-muted">{{ Str::limit($g->description, 60) }}</small></td>
                <td><span class="badge bg-info">{{ $g->api_format }}</span></td>
                <td><code class="small">{{ Str::limit($g->base_url, 40) ?: '-' }}</code></td>
                <td>@if($g->sandbox_mode)<span class="badge bg-warning text-dark">Sandbox</span>@else<span class="badge bg-success">Production</span>@endif</td>
                <td>@if($g->is_default)<span class="badge bg-primary">Default</span>@endif</td>
                <td>@if($g->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Off</span>@endif</td>
                <td>
                    <a href="{{ route('payment-gateways.edit', $g) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('payment-gateways.destroy', $g) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada Payment Gateway. <a href="{{ route('payment-gateways.create') }}">Setup yang pertama</a>.</td></tr>@endforelse
        </tbody>
    </table>
    </div>
</div></div>

<div class="card mt-3 bg-light"><div class="card-body small">
    <h6>Webhook URL untuk daftarkan di provider PG Anda:</h6>
    <code>{{ url('/payment/callback/{TOKEN}') }}</code>
    <p class="text-muted mb-0 mt-1">PG akan POST ke URL ini saat status transaksi berubah. Token disubstitusi otomatis per payment link.</p>
</div></div>
@endsection
