@extends('layouts.app')
@section('title', 'Import CSV Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Import CSV Produk</h4>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">File CSV</label>
                        <input type="file" name="file" id="file" class="form-control form-control-sm @error('file') is-invalid @enderror" accept=".csv,.txt" required>
                        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Format: CSV. Max 2MB.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Import
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Format CSV</h6>
            </div>
            <div class="card-body">
                <p class="small">Kolom yang diperlukan dalam CSV:</p>
                <code>code,name,product_type_id,unit_id,price,cost_price,current_stock,minimum_stock,rack_location,supplier_id,warranty,description</code>
                <p class="small mt-2 text-muted">Contoh baris:</p>
                <pre class="small bg-light p-2 rounded"><code>OLI-001,Oli Mesin 1L,1,1,55000,48000,50,10,Rak A-1,1,1 tahun,Oli mesin synthetic</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection
