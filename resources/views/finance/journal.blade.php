@extends('layouts.app')
@section('title', 'Journal Entries')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-book me-2"></i>Journal Entries</h4>
    <a href="{{ route('finance.journal.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Buat Jurnal</a>
</div>
<div class="card"><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>No</th><th>Tanggal</th><th>Deskripsi</th><th class="text-end">Total Debit</th><th class="text-end">Total Kredit</th></tr></thead><tbody>
@forelse($entries as $e)
<tr>
    <td><code>{{ $e->entry_number }}</code></td><td>{{ $e->entry_date->format('d/m/Y') }}</td><td>{{ $e->description }}</td>
    <td class="text-end">@money($e->lines->sum('debit'))</td><td class="text-end">@money($e->lines->sum('credit'))</td>
</tr>
@empty
<tr><td colspan="5" class="text-center py-3 text-muted">Belum ada jurnal.</td></tr>
@endforelse
</tbody></table></div></div></div>
{{ $entries->links() }}
@endsection
