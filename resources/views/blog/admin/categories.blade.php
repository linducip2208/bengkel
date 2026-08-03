@extends('layouts.app')
@section('title', 'Kategori Blog')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-folder-tree me-2"></i>Kategori Blog</h4>
    <a href="{{ route('blog.admin.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Artikel</a>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header"><strong>Tambah Kategori</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('blog.admin.categories.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" rows="2" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Nama</th><th>Deskripsi</th><th></th></tr></thead>
                    <tbody>
                        @forelse($categories as $cat)
                        <tr>
                            <td><strong>{{ $cat->name }}</strong></td>
                            <td><small>{{ Str::limit($cat->description, 50) }}</small></td>
                            <td>
                                <form action="{{ route('blog.admin.categories.destroy', $cat) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
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
        </div>
    </div>
</div>
@endsection
