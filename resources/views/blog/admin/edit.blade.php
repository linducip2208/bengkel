@extends('layouts.app')
@section('title', 'Edit Artikel')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-pen-to-square me-2"></i>Edit Artikel</h4>
    <a href="{{ route('blog.admin.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('blog.admin.update', $post) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-9">
                    <label class="form-label">Judul *</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $post->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $post->slug) }}">
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">-- Tanpa Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $post->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Featured Image URL</label>
                    <input type="text" name="featured_image" class="form-control @error('featured_image') is-invalid @enderror" value="{{ old('featured_image', $post->featured_image) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Publish</label>
                    <input type="datetime-local" name="published_at" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Excerpt</label>
                    <textarea name="excerpt" rows="2" class="form-control @error('excerpt') is-invalid @enderror" placeholder="Ringkasan singkat (max 500 karakter)">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Konten (HTML) *</label>
                    <textarea name="content" rows="15" class="form-control @error('content') is-invalid @enderror" placeholder="<p>Tulis konten HTML di sini...</p>" required>{{ old('content', $post->content) }}</textarea>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Meta Title (SEO)</label>
                    <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $post->meta_title) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Meta Description (SEO)</label>
                    <input type="text" name="meta_description" class="form-control" value="{{ old('meta_description', $post->meta_description) }}">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">Publish sekarang</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Artikel</button>
                    <a href="{{ route('blog.admin.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
