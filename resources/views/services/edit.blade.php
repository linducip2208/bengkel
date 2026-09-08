@extends('layouts.app')

@section('title', 'Edit Servis')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    /* ===== Scoped: services/edit — workshop service form ===== */
    .svc-edit { max-width: 100%; }
    .svc-edit [class*='col-'] { min-width: 0; }
    .svc-edit .form-control,
    .svc-edit .form-select { min-width: 0; max-width: 100%; }
    .svc-edit input[type='datetime-local'] { min-width: 0; }

    .svc-edit .card { border-radius: 12px; }
    .svc-edit .card-body { padding: 1.25rem; }
    @media (max-width: 767.98px) {
        .svc-edit .card-body { padding: 1rem; }
    }

    .svc-edit .card-header {
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: var(--text-muted);
        border-color: var(--border);
    }

    .svc-edit .form-label {
        font-size: .8rem;
        font-weight: 600;
        margin-bottom: .3rem;
    }

    /* Select2 — always fill its column, never push the page wide */
    .svc-edit .select2-container {
        width: 100% !important;
        max-width: 100%;
    }
    .svc-edit .select2-container .select2-selection {
        min-width: 0;
        max-width: 100%;
    }

    /* Technician toggle selector */
    .svc-tech {
        display: flex;
        align-items: center;
        gap: .5rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: .5rem .65rem;
        min-width: 0;
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease;
    }
    .svc-tech:hover { border-color: var(--bs-primary); }
    .svc-tech.checked {
        border-color: var(--bs-primary);
        background: rgba(13, 110, 253, .07);
    }
    .svc-tech .svc-tech-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: .85rem;
        font-weight: 500;
        color: var(--text);
    }
    .svc-tech .form-check-input { flex-shrink: 0; margin-top: 0; }

    /* Estimate / stat blocks */
    .svc-stat-label {
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--text-muted);
        white-space: nowrap;
    }
    .svc-stat-value { font-size: .95rem; color: var(--text); }

    @media (max-width: 575.98px) {
        .svc-edit .card-header { padding: .6rem 1rem; }
    }
</style>
@endpush

@section('content')
<div class="svc-edit row justify-content-center">
    <div class="col-12 col-lg-10 min-w-0">

        {{-- ===== Page header ===== --}}
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div class="min-w-0">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-semibold">Edit Servis</h5>
                    <span class="badge bg-light text-dark border font-monospace">{{ $service->job_no }}</span>
                </div>
                <div class="mt-1">
                    <span class="badge bg-{{ $service->status_color }} bg-opacity-10 text-{{ $service->status_color }} rounded-pill">{{ $service->status_label }}</span>
                </div>
            </div>
            <a href="{{ route('services.show', $service) }}" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <form action="{{ route('services.update', $service) }}" method="POST">
            @csrf @method('PUT')

            {{-- ===== Informasi Servis ===== --}}
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-circle-info me-2"></i>Informasi Servis</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label" for="customerSelect">Pelanggan <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customerSelect" class="form-select @error('customer_id') is-invalid @enderror" required>
                                <option value="{{ $selectedCustomer->id }}" selected>{{ $selectedCustomer->name }} ({{ $selectedCustomer->phone ?? '-' }})</option>
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label" for="vehicleSelect">Kendaraan <span class="text-danger">*</span></label>
                            <select name="vehicle_id" id="vehicleSelect" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                                @foreach($selectedCustomer->vehicles as $v)
                                    <option value="{{ $v->id }}" {{ $service->vehicle_id == $v->id ? 'selected' : '' }}>
                                        {{ $v->number_plate }} - {{ $v->vehicleBrand->vehicle_brand ?? '' }} {{ $v->model_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Kategori Perbaikan <span class="text-danger">*</span></label>
                            <select name="repair_category_id" class="form-select @error('repair_category_id') is-invalid @enderror" required>
                                @foreach($repairCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ $service->repair_category_id == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->repair_category_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('repair_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Tanggal Servis <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="service_date" class="form-control @error('service_date') is-invalid @enderror"
                                   value="{{ old('service_date', $service->service_date->format('Y-m-d\TH:i')) }}" required>
                            @error('service_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Estimasi Durasi Pengerjaan</label>
                            <div class="input-group">
                                <input type="number" name="estimated_hours" class="form-control @error('estimated_hours') is-invalid @enderror"
                                       value="{{ old('estimated_hours', $service->estimated_hours) }}" step="0.5" min="0.5" max="24" placeholder="2.5">
                                <span class="input-group-text">jam</span>
                            </div>
                            <small class="text-muted">Perkiraan lama pengerjaan, bukan estimasi biaya.</small>
                            @error('estimated_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Service Advisor</label>
                            <select name="service_advisor_id" class="form-select @error('service_advisor_id') is-invalid @enderror">
                                <option value="">-- Pilih Service Advisor --</option>
                                @foreach($serviceAdvisors as $sa)
                                    <option value="{{ $sa->id }}" {{ old('service_advisor_id', $service->service_advisor_id) == $sa->id ? 'selected' : '' }}>
                                        {{ $sa->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_advisor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $service->title) }}" placeholder="Masukkan judul servis..." required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Deskripsi keluhan / pekerjaan...">{{ old('description', $service->description) }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Estimasi / Biaya ===== --}}
            @php $hasEstimate = $service->estimates()->exists(); @endphp

            @if($hasEstimate)
            {{-- Commercial value is owned by ServiceEstimate — read-only here. --}}
            @php
                $activeEstimate = $service->estimates()->with('groups')->orderByDesc('version')->first();
                $commercialTotal = (float) ($activeEstimate->approved_total > 0 ? $activeEstimate->approved_total : $activeEstimate->grand_total);
            @endphp
            <div class="card mb-3 border-warning">
                <div class="card-header"><i class="fas fa-file-signature text-warning me-2"></i>Estimasi Aktif</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="fw-semibold font-monospace">{{ $activeEstimate->estimate_number }}</span>
                                <span class="badge bg-{{ $activeEstimate->statusColor() }}">{{ $activeEstimate->statusLabel() }}</span>
                            </div>
                            <small class="text-muted d-block mt-1">Biaya komersial dikelola pada dokumen estimasi, bukan di form ini.</small>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3 flex-shrink-0">
                            <div class="svc-stat">
                                <div class="svc-stat-label">Versi</div>
                                <div class="svc-stat-value fw-semibold">v{{ $activeEstimate->version }}</div>
                            </div>
                            <div class="svc-stat">
                                <div class="svc-stat-label">{{ $activeEstimate->approved_total > 0 ? 'Nilai Disetujui' : 'Total Estimasi' }}</div>
                                <div class="svc-stat-value fw-bold">@money($commercialTotal)</div>
                            </div>
                            <a href="{{ route('services.show', $service) }}#tab-estimate" class="btn btn-sm btn-outline-warning flex-shrink-0">
                                <i class="fas fa-up-right-from-square me-1"></i> Buka Estimasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @else
            {{-- Legacy service without estimate: keep charge editable, clearly labeled. --}}
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-money-bill me-2"></i>Biaya Manual Lama <span class="badge bg-secondary ms-1" style="text-transform:none;">Legacy</span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label">Biaya (Rp)</label>
                            <input type="number" name="charge" class="form-control @error('charge') is-invalid @enderror"
                                   value="{{ old('charge', $service->charge) }}" step="0.01" min="0">
                            @error('charge') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted d-block mt-1">Nilai manual lama. Akan digantikan oleh dokumen estimasi setelah estimasi dibuat.</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ===== Teknisi ===== --}}
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-user-gear me-2"></i>Teknisi Ditugaskan</div>
                <div class="card-body">
                    @if($technicians->isEmpty())
                        <p class="text-muted small mb-0">Belum ada teknisi terdaftar.</p>
                    @else
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-2">
                            @foreach($technicians as $tech)
                            <div class="col">
                                <label class="svc-tech" for="tech{{ $tech->id }}">
                                    <input class="form-check-input" type="checkbox" name="assign_to[]" value="{{ $tech->id }}"
                                           id="tech{{ $tech->id }}" data-svc-tech
                                           {{ in_array($tech->id, old('assign_to', $service->technicians->pluck('id')->toArray())) ? 'checked' : '' }}>
                                    <span class="svc-tech-name">{{ $tech->name }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ===== Actions ===== --}}
            <div class="d-flex justify-content-end gap-2 pb-1">
                <a href="{{ route('services.show', $service) }}" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $('#customerSelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'Cari pelanggan...',
        allowClear: true,
        width: '100%',
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

    // Technician toggle styling (keeps native checkbox semantics + name="assign_to[]")
    document.querySelectorAll('[data-svc-tech]').forEach(function(cb) {
        var sync = function() {
            var wrap = cb.closest('.svc-tech');
            if (wrap) { wrap.classList.toggle('checked', cb.checked); }
        };
        cb.addEventListener('change', sync);
        sync();
    });
</script>
@endpush
