@extends('layouts.app')
@section('title', 'Printer')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-print me-2"></i>Printer</h4>
    <a href="{{ route('printers.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>#</th><th>Nama</th><th>Tipe</th><th>Alamat</th><th>Cabang</th><th class="text-center">Default</th><th class="text-center">Aktif</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($printers as $printer)
                <tr>
                    <td>{{ $loop->iteration + $printers->firstItem() - 1 }}</td>
                    <td>{{ $printer->name }}</td>
                    <td><span class="badge bg-info">{{ strtoupper($printer->type) }}</span></td>
                    <td>{{ $printer->ip_address ? $printer->ip_address . ':' . $printer->port : '—' }}</td>
                    <td>{{ $printer->branch?->name ?? '—' }}</td>
                    <td class="text-center">@if($printer->is_default)<span class="badge bg-success">Default</span>@else<span class="text-muted">—</span>@endif</td>
                    <td class="text-center">@if($printer->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Off</span>@endif</td>
                    <td class="text-end">
                        <a href="{{ route('printers.edit', $printer) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('printers.destroy', $printer) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-3 text-muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $printers->links() }}</div>
</div></div>
@endsection
