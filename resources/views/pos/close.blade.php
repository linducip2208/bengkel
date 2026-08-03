@extends('layouts.app')
@section('title', 'Tutup Sesi Kasir')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-lock me-2"></i>Tutup Sesi Kasir</h4>
    <a href="{{ route('pos.terminal') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>
<div class="row justify-content-center">
    <div class="col-md-7">
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
                        <label class="form-label">Saldo Akhir di Laci (Closing Balance) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="closing_balance" id="closingBalance" class="form-control text-end" value="{{ $expectedBalance }}" min="0" step="1000" required autofocus>
                        </div>
                        <small class="text-muted">Hitung uang fisik di laci, masukkan apa adanya.</small>
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
    function update() {
        const closing = parseFloat(closingInput.value || 0);
        const diff = closing - expected;
        diffEl.textContent = 'Rp ' + diff.toLocaleString('id-ID');
        diffEl.className = 'ms-2 ' + (diff === 0 ? 'text-success' : (diff > 0 ? 'text-info' : 'text-danger'));
    }
    closingInput.addEventListener('input', update);
    update();
</script>
@endsection
