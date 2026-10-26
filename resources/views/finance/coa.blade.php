@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-list-ol me-2"></i>Chart of Accounts</h4>
    @can('finance-coa.create')
    <a href="{{ route('finance.coa.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Akun</a>
    @endcan
</div>
@php $types = ['asset'=>'Aset','liability'=>'Liabilitas','equity'=>'Ekuitas','income'=>'Pendapatan','expense'=>'Beban']; $hasAccounts = collect($types)->contains(fn ($label, $key) => ($accounts[$key] ?? collect())->count() > 0); @endphp
@if(!$hasAccounts)
<div class="card"><div class="card-body">
    @include('partials.empty-state', ['icon' => 'fa-list-ol', 'title' => 'Belum ada akun', 'message' => 'Tambahkan akun pertama untuk mulai pembukuan.', 'actionUrl' => auth()->user()?->can('finance-coa.create') ? route('finance.coa.create') : null, 'actionLabel' => 'Tambah Akun'])
</div></div>
@endif
@foreach($types as $key=>$label)
    @if(($accounts[$key] ?? collect())->count())
    <div class="card mb-3"><div class="card-header"><strong>{{ $label }}</strong></div><div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-sm mb-0"><thead><tr><th>Kode</th><th>Nama Akun</th><th></th></tr></thead><tbody>
        @foreach($accounts[$key] as $acc)
        <tr><td><code>{{ $acc->code }}</code></td><td>{{ $acc->name }}</td><td>
            @can('finance-coa.delete')
            <form action="{{ route('finance.coa.destroy', $acc) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus akun {{ $acc->code }} - {{ $acc->name }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="Hapus akun {{ $acc->name }}"><i class="fas fa-trash"></i></button></form>
            @endcan
        </td></tr>
        @endforeach
    </tbody></table></div></div></div>
    @endif
@endforeach
@endsection
