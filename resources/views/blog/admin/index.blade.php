@extends('layouts.app')
@section('title', 'Kelola Blog')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-blog me-2"></i>Artikel Blog</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('blog.admin.categories') }}" class="btn btn-outline-secondary"><i class="fas fa-folder me-1"></i>Kategori</a>
        <a href="{{ route('blog.admin.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tulis Artikel</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari judul..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="fas fa-search me-1"></i>Cari</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                    <tr>
                        <td>
                            <strong>{{ $post->title }}</strong>
                            <div class="text-muted small">{{ Str::limit($post->excerpt, 80) }}</div>
                        </td>
                        <td>{{ $post->category?->name ?? '-' }}</td>
                        <td>
                            @if($post->is_published)
                                <span class="badge bg-success">Published</span>
                            @else
                                <span class="badge bg-secondary">Draft</span>
                            @endif
                        </td>
                        <td><small>{{ $post->created_at->format('d/m/Y') }}</small></td>
                        <td class="text-end">
                            <a href="{{ route('blog.admin.edit', $post) }}" class="btn btn-sm btn-outline-warning me-1" title="Edit"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('blog.admin.destroy', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus artikel ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada artikel. <a href="{{ route('blog.admin.create') }}">Tulis sekarang</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $posts->links() }}</div>
    </div>
</div>
@endsection
