@extends('layouts.app')
@section('title', 'Manajemen User')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-people me-2"></i>Manajemen User</h4>
    <div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-info"><i class="bi bi-shield-lock me-1"></i>Kelola Role & Permission</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-lg me-1"></i>Tambah User</button>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card"><div class="card-body">
    <form method="GET" class="mb-3"><div class="input-group" style="max-width: 360px;">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama / email...">
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </div></form>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>2FA</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($users as $u)
            <tr>
                <td><strong>{{ $u->name }}</strong></td>
                <td>{{ $u->email }}</td>
                <td>@foreach($u->roles as $r)<span class="badge bg-info">{{ $r->name }}</span>@endforeach</td>
                <td>@if($u->is_active ?? true)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Off</span>@endif</td>
                <td class="small">{{ $u->updated_at?->diffForHumans() }}</td>
                <td>@if($u->two_factor_enabled)<span class="badge bg-success">ON</span>@else<span class="badge bg-light text-dark">OFF</span>@endif</td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
                    @if($u->id !== auth()->id())
                    <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user {{ $u->name }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                    @endif
                </td>
            </tr>

            <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('users.update', $u) }}" method="POST" class="modal-content">
                        @csrf @method('PUT')
                        <div class="modal-header"><h5 class="modal-title">Edit {{ $u->name }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" value="{{ $u->name }}" required></div>
                            <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ $u->email }}" required></div>
                            <div class="mb-2"><label class="form-label">Password Baru (kosongkan kalau tidak diganti)</label><input type="password" name="password" class="form-control" minlength="6"></div>
                            <div class="mb-2"><label class="form-label">Role</label><select name="role" class="form-select" required>
                                @foreach($roles as $r)<option value="{{ $r->name }}" {{ $u->hasRole($r->name) ? 'selected' : '' }}>{{ $r->name }}</option>@endforeach
                            </select></div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $u->id }}" {{ ($u->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label" for="active{{ $u->id }}">Aktif</label></div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
                    </form>
                </div>
            </div>
            @empty<tr><td colspan="7" class="text-center text-muted py-3">Belum ada user.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $users->links() }}
</div></div>

<div class="modal fade" id="addUserModal" tabindex="-1"><div class="modal-dialog"><form action="{{ route('users.store') }}" method="POST" class="modal-content">
    @csrf
    <div class="modal-header"><h5>Tambah User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nama <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" name="email" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" name="password" class="form-control" required minlength="6"></div>
        <div class="mb-2"><label class="form-label">Role <span class="text-danger">*</span></label><select name="role" class="form-select" required>
            @foreach($roles as $r)<option value="{{ $r->name }}">{{ $r->name }}</option>@endforeach
        </select></div>
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="newActive" checked><label class="form-check-label" for="newActive">Aktif</label></div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-success">Buat User</button></div>
</form></div></div>
@endsection
