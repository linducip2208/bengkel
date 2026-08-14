@extends('layouts.app')
@section('title', 'Klaim Supplier')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-hand-holding-usd me-2"></i>Klaim Supplier</h4>
    <a href="{{ route('supplier-claims.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Buat Klaim</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach(['pending','submitted','approved','rejected','paid'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>No. Klaim</th>
                    <th>Supplier</th>
                    <th>Produk</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Nilai Klaim</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $c)
                <tr>
                    <td><a href="{{ route('supplier-claims.show', $c) }}"><code>{{ $c->claim_number }}</code></a></td>
                    <td>{{ $c->supplier?->name ?? '-' }}</td>
                    <td>{{ $c->product?->name ?? '-' }}</td>
                    <td class="text-end">{{ number_format((float) $c->quantity, 0, ',', '.') }}</td>
                    <td class="text-end">@money($c->claim_amount)</td>
                    <td>
                        @php
                            $badges = ['pending'=>'secondary','submitted'=>'info','approved'=>'success','rejected'=>'danger','paid'=>'primary'];
                        @endphp
                        <span class="badge bg-{{ $badges[$c->status] ?? 'secondary' }}">{{ ucfirst($c->status) }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('supplier-claims.show', $c) }}" class="btn btn-sm btn-outline-primary me-1" title="Detail"><i class="fas fa-eye"></i></a>
                        <form action="{{ route('supplier-claims.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus klaim ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada klaim supplier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $claims->links() }}</div>
</div></div>
@endsection
