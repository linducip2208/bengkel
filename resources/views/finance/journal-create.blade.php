@extends('layouts.app')
@section('title', 'Buat Jurnal')
@section('content')
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('finance.journal.store') }}" id="journalForm">@csrf
    <div class="row g-3 mb-3">
        <div class="col-md-3"><label>Tanggal *</label><input type="date" name="entry_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="col-md-9"><label>Deskripsi</label><input type="text" name="description" class="form-control" placeholder="Keterangan jurnal..."></div>
    </div>
    <table class="table table-bordered" id="linesTable"><thead><tr><th>Akun *</th><th>Debit (Rp)</th><th>Kredit (Rp)</th><th></th></tr></thead><tbody><tr id="line0">
        <td><select name="lines[0][account_id]" class="form-select" required><option value="">-- Pilih Akun --</option>@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach</select></td>
        <td><input type="number" name="lines[0][debit]" class="form-control debit" value="0" min="0" oninput="checkBalance()"></td>
        <td><input type="number" name="lines[0][credit]" class="form-control credit" value="0" min="0" oninput="checkBalance()"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
    </tr></tbody><tfoot><tr><td colspan="3" class="text-end"><strong>Selisih:</strong></td><td><span id="balance" class="fw-bold"></span></td></tr></tfoot></table>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addLine()"><i class="fas fa-plus"></i> Tambah Baris</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Jurnal</button>
</form></div></div>
@push('scripts')
<script>
let lineCount = 1;
function addLine(){
    const tbody = document.querySelector('#linesTable tbody');
    const tr = document.createElement('tr');
    tr.id = `line${lineCount}`;
    tr.innerHTML = `<td><select name="lines[${lineCount}][account_id]" class="form-select" required><option value="">-- Pilih Akun --</option>@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach</select></td>
        <td><input type="number" name="lines[${lineCount}][debit]" class="form-control debit" value="0" min="0" oninput="checkBalance()"></td>
        <td><input type="number" name="lines[${lineCount}][credit]" class="form-control credit" value="0" min="0" oninput="checkBalance()"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove();checkBalance()"><i class="fas fa-trash"></i></button></td>`;
    tbody.appendChild(tr);
    lineCount++;
}
function checkBalance(){
    let d=0,c=0;
    document.querySelectorAll('.debit').forEach(el=>d+=parseFloat(el.value)||0);
    document.querySelectorAll('.credit').forEach(el=>c+=parseFloat(el.value)||0);
    const diff = Math.abs(d-c);
    const el = document.getElementById('balance');
    el.textContent = 'Rp '+diff.toLocaleString('id-ID');
    el.className = 'fw-bold ' + (diff === 0 ? 'text-success' : 'text-danger');
}
</script>
@endpush
@endsection
