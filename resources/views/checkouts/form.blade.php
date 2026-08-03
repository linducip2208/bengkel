@extends('layouts.app')

@section('title', 'Checkout Servis')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clipboard-check text-success me-2"></i>Checkout: {{ $service->job_no }}</span>
                <a href="{{ route('services.show', $service) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="mb-3 p-3 bg-light rounded">
                    <strong>{{ $service->customer->name ?? '-' }}</strong> |
                    {{ $service->vehicle->number_plate ?? '-' }} |
                    {{ $service->title }}
                </div>

                <form action="{{ route('checkouts.store', $service) }}" method="POST">
                    @csrf

                    <div id="checkoutContainer">
                        @forelse($service->checkoutResults as $i => $cr)
                        <div class="checkout-row row g-2 mb-3">
                            <div class="col-md-4">
                                <select name="results[{{ $i }}][checkout_category_id]" class="form-select form-select-sm">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ $cr->checkout_category_id == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="results[{{ $i }}][result]" class="form-control form-control-sm"
                                       value="{{ $cr->result }}" placeholder="Hasil...">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="results[{{ $i }}][comment]" class="form-control form-control-sm"
                                       value="{{ $cr->comment }}" placeholder="Komentar...">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-checkout-row">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="checkout-row row g-2 mb-3">
                            <div class="col-md-4">
                                <select name="results[0][checkout_category_id]" class="form-select form-select-sm">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="results[0][result]" class="form-control form-control-sm" placeholder="Hasil...">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="results[0][comment]" class="form-control form-control-sm" placeholder="Komentar...">
                            </div>
                            <div class="col-md-1"></div>
                        </div>
                        @endforelse
                    </div>

                    <button type="button" id="addCheckoutRow" class="btn btn-sm btn-outline-primary mb-3">
                        <i class="fas fa-plus me-1"></i> Tambah Kategori
                    </button>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('services.show', $service) }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save me-1"></i> Simpan Checkout
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var checkoutIndex = {{ $service->checkoutResults->count() }};

    document.getElementById('addCheckoutRow').addEventListener('click', function() {
        var container = document.getElementById('checkoutContainer');
        var row = document.createElement('div');
        row.className = 'checkout-row row g-2 mb-3';
        row.innerHTML = `
            <div class="col-md-4">
                <select name="results[${checkoutIndex}][checkout_category_id]" class="form-select form-select-sm">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="results[${checkoutIndex}][result]" class="form-control form-control-sm" placeholder="Hasil...">
            </div>
            <div class="col-md-3">
                <input type="text" name="results[${checkoutIndex}][comment]" class="form-control form-control-sm" placeholder="Komentar...">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-checkout-row">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
        checkoutIndex++;
    });

    document.getElementById('checkoutContainer').addEventListener('click', function(e) {
        if (e.target.closest('.remove-checkout-row')) {
            var rows = document.querySelectorAll('.checkout-row');
            if (rows.length > 1) {
                e.target.closest('.checkout-row').remove();
            }
        }
    });
</script>
@endpush
