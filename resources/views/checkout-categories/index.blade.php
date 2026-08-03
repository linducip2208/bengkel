@extends('layouts.app')
@section('title', 'Kategori Checkout')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-sign-out-alt me-2"></i>Kategori Checkout</h4>
    <a href="{{ route('checkout-categories.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width: 360px;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari kategori...">
            <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>#</th><th>Nama Kategori</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($categories as $c)
                <tr>
                    <td>{{ $loop->iteration + $categories->firstItem() - 1 }}</td>
                    <td>{{ $c->category_name }}</td>
                    <td class="text-end">
                        <a href="{{ route('checkout-categories.edit', $c) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('checkout-categories.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-3 text-muted">Belum ada kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $categories->links() }}</div>
</div></div>
@endsection
