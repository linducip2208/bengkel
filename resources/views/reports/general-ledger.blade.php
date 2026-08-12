@extends('layouts.app')

@section('title')
    General Ledger — {{ config('app.name') }}
@stop

@section('content')
<h4 class="mb-3">General Ledger</h4>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Account</label>
                <select name="account_id" class="form-select">
                    <option value="">— All Accounts —</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->code }} — {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="{{ route('reports.general-ledger') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h4 class="text-primary">{{ $totalEntries }}</h4>
                <p class="text-muted">Total Entries</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h4 class="text-success">@money($totalDebit)</h4>
                <p class="text-muted">Total Debit</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h4 class="text-danger">@money($totalCredit)</h4>
                <p class="text-muted">Total Credit</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h4 class="text-info">@money($totalDebit - $totalCredit)</h4>
                <p class="text-muted">Balance</p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mb-3 gap-2 no-print">
    <a href="{{ route('reports.export-pdf', ['type' => 'general-ledger'] + request()->all()) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf"></i> Export PDF</a>
    <a href="{{ route('reports.export-excel', ['type' => 'general-ledger'] + request()->all()) }}" class="btn btn-success btn-sm"><i class="bi bi-file-excel"></i> Export Excel</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Entry #</th>
                        <th>Account</th>
                        <th>Description</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $running = 0; @endphp
                    @forelse($entries as $entry)
                        @php
                            $lineDebit = $entry->total_debit ?? 0;
                            $lineCredit = $entry->total_credit ?? 0;
                            $running += ($lineDebit - $lineCredit);
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($entry->entry_date)->format('d/m/Y') }}</td>
                            <td>{{ $entry->entry_number }}</td>
                            <td>{{ $entry->account_name ?? $entry->account_code ?? '-' }}</td>
                            <td>{{ $entry->description }}</td>
                            <td class="text-end">@money($lineDebit)</td>
                            <td class="text-end">@money($lineCredit)</td>
                            <td class="text-end fw-bold {{ $running >= 0 ? 'text-success' : 'text-danger' }}">@money($running)</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No journal entries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@stop
