@extends('layouts.app')
@section('title', 'Numbering')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-hashtag me-2"></i>Numbering</h4>
    <a href="{{ route('invoice-schemes.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>#</th><th>Nama</th><th>Prefix</th><th>Format</th><th class="text-end">Next Number</th><th>Cabang</th><th class="text-center">Default</th><th class="text-center">Aktif</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($schemes as $scheme)
                <tr>
                    <td>{{ $loop->iteration + $schemes->firstItem() - 1 }}</td>
                    <td>{{ $scheme->name }}</td>
                    <td><span class="badge bg-secondary">{{ $scheme->prefix }}</span></td>
                    <td><code>{{ $scheme->format }}</code></td>
                    <td class="text-end">{{ $scheme->next_number }}</td>
                    <td>{{ $scheme->branch?->name ?? '—' }}</td>
                    <td class="text-center">@if($scheme->is_default)<span class="badge bg-success">Default</span>@else<span class="text-muted">—</span>@endif</td>
                    <td class="text-center">@if($scheme->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Off</span>@endif</td>
                    <td class="text-end">
                        <a href="{{ route('invoice-schemes.edit', $scheme) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('invoice-schemes.destroy', $scheme) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-3 text-muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $schemes->links() }}</div>
</div></div>
@endsection
