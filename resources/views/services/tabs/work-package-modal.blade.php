{{-- Modal: create work package (from finding or manual) --}}
<div class="modal fade" id="wpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('services.work-packages.store', $service) }}">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-briefcase me-1"></i> Work Package Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-7">
                        <label class="form-label small">Judul Pekerjaan <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="wpTitle" class="form-control form-control-sm" required placeholder="Contoh: GANTI KAMPAS REM DEPAN">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Sumber Finding</label>
                        <select name="service_finding_id" id="wpFinding" class="form-select form-select-sm">
                            <option value="">— Manual (tanpa finding) —</option>
                            @foreach($service->findings->filter(fn ($f) => $f->isActive()) as $f)
                                <option value="{{ $f->id }}" data-title="{{ $f->title }}" data-severity="{{ $f->severity }}">
                                    {{ $f->finding_number }} · {{ $f->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">Standard Time (menit)</label>
                        <input type="number" name="standard_minutes" id="wpMinutes" class="form-control form-control-sm" min="0" value="30">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small">Deskripsi</label>
                        <input type="text" name="description" id="wpDescription" class="form-control form-control-sm">
                    </div>
                </div>

                <label class="form-label small fw-bold">Item Pekerjaan</label>
                <div id="wpItems">
                    <div class="row g-1 wp-item mb-1">
                        <div class="col-md-2">
                            <select name="items[0][item_type]" class="form-select form-select-sm">
                                <option value="labor">Jasa</option>
                                <option value="part">Part</option>
                                <option value="other">Lain</option>
                            </select>
                        </div>
                        <div class="col-md-4"><input type="text" name="items[0][description]" class="form-control form-control-sm" placeholder="Deskripsi" required></div>
                        <div class="col-md-2"><input type="number" step="0.001" name="items[0][quantity]" class="form-control form-control-sm" placeholder="Qty" value="1" required></div>
                        <div class="col-md-2"><input type="number" step="1" name="items[0][unit_price]" class="form-control form-control-sm" placeholder="Harga" required></div>
                        <div class="col-md-2"><input type="number" name="items[0][standard_minutes]" class="form-control form-control-sm" placeholder="Menit"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="wpAddItem"><i class="fas fa-plus me-1"></i> Baris</button>

                <div class="alert alert-warning small mt-2 mb-0">
                    Work package belum memiliki harga final sampai disetujui customer via estimasi. Estimasi dibuat dari work package di tab Estimasi.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-warning">Simpan Work Package</button>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    'use strict';
    var modal = document.getElementById('wpModal');
    if (! modal) { return; }

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (! trigger) { return; }
        var findingId = trigger.getAttribute('data-finding-id');
        var findingTitle = trigger.getAttribute('data-finding-title') || '';
        var severity = trigger.getAttribute('data-severity') || '';
        var measurement = trigger.getAttribute('data-measurement') || '';

        var select = document.getElementById('wpFinding');
        if (findingId) {
            select.value = findingId;
            var title = document.getElementById('wpTitle');
            if (title && ! title.value) {
                title.value = 'GANTI ' + findingTitle.toUpperCase();
            }
            var desc = document.getElementById('wpDescription');
            if (desc && ! desc.value && measurement) {
                desc.value = findingTitle + ' (' + measurement + ')';
            }
        }
    });

    var addBtn = document.getElementById('wpAddItem');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            var container = document.getElementById('wpItems');
            var index = container.querySelectorAll('.wp-item').length;
            var first = container.querySelector('.wp-item');
            var row = document.createElement('div');
            row.className = 'row g-1 wp-item mb-1';
            row.innerHTML = first.innerHTML.replace(/\[0\]/g, '[' + index + ']').replace(/value="[^"]*"/g, '');
            container.appendChild(row);
        });
    }
})();
</script>
@endpush
@endonce
