@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-list-ol me-2"></i>Chart of Accounts</h4>
    <a href="{{ route('finance.coa.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Akun</a>
</div>
@php $types = ['asset'=>'Aset','liability'=>'Liabilitas','equity'=>'Ekuitas','income'=>'Pendapatan','expense'=>'Beban']; @endphp
@foreach($types as $key=>$label)
    @if(($accounts[$key] ?? collect())->count())
    <div class="card mb-3"><div class="card-header"><strong>{{ $label }}</strong></div><div class="card-body p-0">
    <table class="table table-sm mb-0"><thead><tr><th>Kode</th><th>Nama Akun</th><th></th></tr></thead><tbody>
        @foreach($accounts[$key] as $acc)
        <tr><td><code>{{ $acc->code }}</code></td><td>{{ $acc->name }}</td><td>
            <form action="{{ route('finance.coa.destroy', $acc) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
        </td></tr>
        @endforeach
    </tbody></table></div></div>
    @endif
@endforeach
@endsection
