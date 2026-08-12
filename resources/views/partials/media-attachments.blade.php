@php
    $attachments = $attachable->mediaAttachments;
@endphp
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-paperclip me-1"></i>Dokumen</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('media.store') }}" method="POST" enctype="multipart/form-data" class="mb-3">
            @csrf
            <input type="hidden" name="attachable_type" value="{{ $attachableType }}">
            <input type="hidden" name="attachable_id" value="{{ $attachable->id }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <input type="file" name="file" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-5">
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="Nama dokumen (opsional)">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="fas fa-upload me-1"></i>Unggah
                    </button>
                </div>
            </div>
            <small class="text-muted">Maks 10MB · PDF, JPG, PNG, DOC, DOCX, XLS, XLSX</small>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Ukuran</th>
                        <th>Diunggah</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attachments as $media)
                    <tr>
                        <td>{{ $media->name }}</td>
                        <td><span class="badge bg-secondary">{{ strtoupper(pathinfo($media->file_path, PATHINFO_EXTENSION)) }}</span></td>
                        <td>{{ $media->size ? number_format($media->size / 1024, 1) . ' KB' : '-' }}</td>
                        <td>{{ $media->created_at?->format('d M Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ asset('storage/' . $media->file_path) }}" download="{{ $media->name }}" class="btn btn-sm btn-outline-info" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            <form action="{{ route('media.destroy', $media) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus dokumen ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted">Belum ada dokumen.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
