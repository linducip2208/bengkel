@extends('layouts.app')
@section('title', 'Import Pelanggan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-upload me-2"></i>Import Pelanggan</h4>
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">File CSV / Excel <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            Format: .xlsx, .xls, atau .csv. Maks 10 MB.
                        </div>
                    </div>

                    <div class="alert alert-light border small mb-3">
                        <div class="fw-semibold mb-1">Kolom yang didukung:</div>
                        <code>name</code>, <code>phone</code>, <code>email</code>, <code>address</code>,
                        <code>company_name</code>, <code>tax_id</code>
                        <div class="mt-1 text-muted">Pelanggan dicocokkan berdasarkan <code>phone</code> — jika sudah ada, field lain diperbarui.</div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <a href="{{ asset('import-templates/customer-import-template.csv') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-download me-1"></i> Unduh Template
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i> Import Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if(session('import_errors'))
        <div class="card mt-3 border-danger">
            <div class="card-header bg-danger-subtle text-danger fw-semibold">
                <i class="fas fa-exclamation-triangle me-1"></i> Detail Baris Gagal
            </div>
            <ul class="list-group list-group-flush small">
                @foreach(array_slice(session('import_errors'), 0, 20) as $err)
                    <li class="list-group-item">{{ $err }}</li>
                @endforeach
                @if(count(session('import_errors')) > 20)
                    <li class="list-group-item text-muted">... dan {{ count(session('import_errors')) - 20 }} error lainnya.</li>
                @endif
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
