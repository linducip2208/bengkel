@extends('layouts.app')
@section('title', 'Voucher / Promo')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-ticket-perforated me-2"></i>Voucher / Promo Code</h4>
    <a href="{{ route('vouchers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Voucher</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari kode/nama..."></div>
        <div class="col-md-3"><select name="status" class="form-select">
            <option value="">Semua</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
        </select></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Code</th><th>Nama</th><th>Type</th><th class="text-end">Nilai</th><th>Min</th><th>Pakai</th><th>Berlaku</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($vouchers as $v)
            <tr>
                <td><code class="bg-warning bg-opacity-25 px-2 py-1 rounded">{{ $v->code }}</code></td>
                <td>{{ $v->name }}</td>
                <td><span class="badge bg-info">{{ $v->type }}</span></td>
                <td class="text-end">{{ $v->type === 'percent' ? $v->value . '%' : \App\Models\Currency::format($v->value) }}</td>
                <td class="small">@money($v->min_purchase)</td>
                <td class="small">{{ $v->used_count }}/{{ $v->usage_limit ?? '∞' }}</td>
                <td class="small">{{ $v->valid_from?->format('d/m/y') ?? '-' }} → {{ $v->valid_until?->format('d/m/y') ?? '∞' }}</td>
                <td>@if($v->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Off</span>@endif</td>
                <td>
                    <a href="{{ route('vouchers.edit', $v) }}" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('vouchers.destroy', $v) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty<tr><td colspan="9" class="text-center text-muted py-3">Belum ada voucher.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $vouchers->links() }}
</div></div>
@endsection
