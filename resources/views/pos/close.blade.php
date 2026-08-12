@extends('layouts.app')
@section('title', 'Tutup Sesi Kasir')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-lock me-2"></i>Tutup Sesi Kasir</h4>
    <a href="{{ route('pos.terminal') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Sesi #{{ $session->id }} oleh {{ $session->user->name ?? '-' }}</h6>
                <p class="text-muted small">Dibuka {{ $session->opened_at->format('d M Y H:i') }}</p>

                <table class="table table-borderless mb-4">
                    <tr><td>Saldo Awal</td><td class="text-end">@money($session->opening_balance)</td></tr>
                    <tr><td>Total Transaksi POS</td><td class="text-end">{{ $session->transaction_count }} transaksi</td></tr>
                    <tr><td>Revenue Sesi Ini</td><td class="text-end">@money($session->revenue)</td></tr>
                    <tr class="border-top"><td><strong>Saldo Kas Diharapkan</strong></td><td class="text-end"><strong class="text-primary">@money($expectedBalance)</strong></td></tr>
                </table>

                <form action="{{ route('pos.close', $session) }}" method="POST">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><i class="bi bi-cash-stack me-1"></i>Hitungan Pecahan Uang Fisik</label>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pecahan</th>
                                        <th style="width: 40%;">Jumlah Lembar/Keping</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $denominations = [100000 => 'Rp 100.000', 50000 => 'Rp 50.000', 20000 => 'Rp 20.000', 10000 => 'Rp 10.000', 5000 => 'Rp 5.000', 2000 => 'Rp 2.000', 1000 => 'Rp 1.000', 500 => 'Rp 500', 200 => 'Rp 200', 100 => 'Rp 100'];
                                    @endphp
                                    @foreach($denominations as $value => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>
                                            <input type="hidden" name="denominations[{{ $value }}][denomination]" value="{{ $value }}">
                                            <input type="number" name="denominations[{{ $value }}][count]" class="form-control form-control-sm text-end denom-count"
                                                data-denomination="{{ $value }}" min="0" step="1" value="0" placeholder="0">
                                        </td>
                                        <td class="text-end">
                                            <span class="denom-subtotal" data-denomination="{{ $value }}">Rp 0</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light fw-semibold">
                                        <td colspan="2" class="text-end">Total Uang Fisik</td>
                                        <td class="text-end" id="physicalTotal">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <small class="text-muted">Isi jumlah uang fisik per pecahan. Total otomatis mengisi saldo akhir di laci.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Saldo Akhir di Laci (Closing Balance) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="closing_balance" id="closingBalance" class="form-control text-end" value="{{ $expectedBalance }}" min="0" step="1000" required>
                        </div>
                        <small class="text-muted">Kosongkan pecahan di atas dan hitung uang fisik secara manual, atau biarkan terisi otomatis.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Penutupan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Mis: ada selisih kurang Rp 5.000 karena kembalian tidak pas"></textarea>
                    </div>
                    <div class="alert alert-info">
                        Selisih (Aktual − Diharapkan): <strong id="differenceDisplay" class="ms-2">Rp 0</strong>
                    </div>
                    <button type="submit" class="btn btn-warning btn-lg w-100">
                        <i class="bi bi-lock-fill me-1"></i>Tutup Sesi & Simpan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    const expected = {{ $expectedBalance }};
    const closingInput = document.getElementById('closingBalance');
    const diffEl = document.getElementById('differenceDisplay');
    const physicalTotalEl = document.getElementById('physicalTotal');

    function recalcDenominations() {
        let total = 0;
        document.querySelectorAll('.denom-count').forEach(input => {
            const denom = parseInt(input.dataset.denomination, 10);
            const count = parseInt(input.value || 0, 10);
            const subtotal = denom * count;
            total += subtotal;
            const cell = document.querySelector('.denom-subtotal[data-denomination="' + denom + '"]');
            if (cell) cell.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
        });
        physicalTotalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        closingInput.value = total;
        updateDiff();
    }

    function updateDiff() {
        const closing = parseFloat(closingInput.value || 0);
        const diff = closing - expected;
        diffEl.textContent = 'Rp ' + diff.toLocaleString('id-ID');
        diffEl.className = 'ms-2 ' + (diff === 0 ? 'text-success' : (diff > 0 ? 'text-info' : 'text-danger'));
    }

    document.querySelectorAll('.denom-count').forEach(input => {
        input.addEventListener('input', recalcDenominations);
    });
    closingInput.addEventListener('input', updateDiff);

    recalcDenominations();
</script>
@endsection
