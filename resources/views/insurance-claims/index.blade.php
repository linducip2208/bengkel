@extends('layouts.app')
@section('title', 'Klaim Asuransi')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-file-invoice-dollar me-2"></i>Klaim Asuransi</h4>
    <a href="{{ route('insurance-claims.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Buat Klaim</a>
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
                    <th>Customer</th>
                    <th>Kendaraan</th>
                    <th>Asuransi</th>
                    <th>No. Polis</th>
                    <th>Tgl Klaim</th>
                    <th>Estimasi</th>
                    <th>Disetujui</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $c)
                <tr>
                    <td><a href="{{ route('insurance-claims.show', $c) }}"><code>{{ $c->claim_number }}</code></a></td>
                    <td>{{ $c->customer?->name ?? '-' }}</td>
                    <td>{{ $c->vehicle?->number_plate ?? '-' }}</td>
                    <td>{{ $c->insurance_company ?? '-' }}</td>
                    <td>{{ $c->policy_number ?? '-' }}</td>
                    <td>{{ $c->claim_date?->format('d M Y') }}</td>
                    <td>@money($c->estimated_amount ?? 0)</td>
                    <td>@money($c->approved_amount ?? 0)</td>
                    <td>
                        @php
                            $badges = ['pending'=>'secondary','submitted'=>'info','approved'=>'success','rejected'=>'danger','paid'=>'primary'];
                        @endphp
                        <span class="badge bg-{{ $badges[$c->status] ?? 'secondary' }}">{{ ucfirst($c->status) }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('insurance-claims.show', $c) }}" class="btn btn-sm btn-outline-primary me-1" title="Detail"><i class="fas fa-eye"></i></a>
                        <form action="{{ route('insurance-claims.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus klaim ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center py-4 text-muted">Belum ada klaim asuransi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $claims->links() }}</div>
</div></div>
@endsection
