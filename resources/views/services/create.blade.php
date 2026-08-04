@extends('layouts.app')

@section('title', 'Servis Baru')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-plus-circle text-danger me-2"></i>Servis Baru</span>
                <a href="{{ route('services.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('services.store') }}" method="POST">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customerSelect" class="form-select @error('customer_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Pelanggan --</option>
                                @if(old('customer_id'))
                                    <option value="{{ old('customer_id') }}" selected>Loading...</option>
                                @endif
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kendaraan <span class="text-danger">*</span></label>
                            <select name="vehicle_id" id="vehicleSelect" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Pelanggan dulu --</option>
                                @if(old('vehicle_id'))
                                    <option value="{{ old('vehicle_id') }}" selected>Loading...</option>
                                @endif
                            </select>
                            @error('vehicle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori Perbaikan <span class="text-danger">*</span></label>
                            <select name="repair_category_id" class="form-select @error('repair_category_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($repairCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('repair_category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->repair_category_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('repair_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Servis <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="service_date" class="form-control @error('service_date') is-invalid @enderror"
                                   value="{{ old('service_date', now()->format('Y-m-d\TH:i')) }}" required>
                            @error('service_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estimasi (jam)</label>
                            <input type="number" name="estimated_hours" class="form-control @error('estimated_hours') is-invalid @enderror"
                                   value="{{ old('estimated_hours') }}" step="0.5" min="0.5" max="24" placeholder="2.5">
                            @error('estimated_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Paket Service (Quick Fill)</label>
                        <select class="form-select" id="packageSelect" onchange="fillPackage()">
                            <option value="">-- Pilih Paket (opsional) --</option>
                            @foreach(\App\Models\ServicePackage::where('is_active', true)->orderBy('name')->get() as $pkg)
                                <option value="{{ $pkg->id }}" data-price="{{ $pkg->price }}" data-hours="{{ $pkg->estimated_hours }}" data-desc="{{ $pkg->description }}">{{ $pkg->name }} — @money($pkg->price)</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pilih paket untuk auto-fill judul, biaya, dan estimasi</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Judul Servis <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" placeholder="Masukkan judul servis..." required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Deskripsi keluhan / pekerjaan...">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Biaya (Rp)</label>
                        <input type="number" name="charge" class="form-control @error('charge') is-invalid @enderror"
                               value="{{ old('charge', 0) }}" step="0.01" min="0">
                        @error('charge') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teknisi</label>
                        <div class="row">
                            @foreach($technicians as $tech)
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="assign_to[]" value="{{ $tech->id }}"
                                           id="tech{{ $tech->id }}" {{ in_array($tech->id, old('assign_to', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tech{{ $tech->id }}">{{ $tech->name }}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save me-1"></i> Simpan Servis
                        </button>
                    </div>
                </form>
            </div>
        </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function fillPackage() {
        const sel = document.getElementById('packageSelect');
        const opt = sel.options[sel.selectedIndex];
        if (!opt.value) return;
        document.querySelector('[name="title"]').value = opt.text.split(' — ')[0];
        document.querySelector('[name="charge"]').value = opt.dataset.price;
        document.querySelector('[name="estimated_hours"]').value = opt.dataset.hours || '';
        document.querySelector('[name="description"]').value = opt.dataset.desc || '';
    }
    $('#customerSelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'Cari pelanggan...',
        allowClear: true,
        ajax: {
            url: '{{ route("services.customers.search") }}',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return { id: item.id, text: item.name + ' (' + (item.phone || '-') + ')' };
                    })
                };
            }
        }
    });

    $('#customerSelect').on('change', function() {
        var customerId = $(this).val();
        var vehicleSelect = $('#vehicleSelect');
        vehicleSelect.empty().append('<option value="">-- Pilih Kendaraan --</option>');
        if (customerId) {
            vehicleSelect.prop('disabled', true);
            $.get('{{ route("services.vehicles-by-customer", ["customer" => "__ID__"]) }}'.replace('__ID__', customerId), function(data) {
                vehicleSelect.prop('disabled', false);
                data.forEach(function(v) {
                    vehicleSelect.append('<option value="' + v.id + '">' + v.number_plate + ' - ' + (v.vehicle_brand?.vehicle_brand || '') + ' ' + (v.model_name || '') + '</option>');
                });
            });
        }
    });
</script>
@endpush
