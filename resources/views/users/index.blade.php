@extends('layouts.app')
@section('title', 'Manajemen User')
@section('content')
@php($editingUserId = old('_form') === 'edit-user' ? (int) old('editing_user_id') : null)
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-people me-2"></i>Manajemen User</h4>
    <div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-info"><i class="bi bi-shield-lock me-1"></i>Kelola Role & Permission</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-lg me-1"></i>Tambah User</button>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())
<div class="alert alert-danger" role="alert">
    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-circle me-1"></i>Perubahan belum tersimpan.</div>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="card"><div class="card-body">
    <form method="GET" class="mb-3"><div class="input-group" style="max-width: 360px;">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama / email...">
        <button type="submit" class="btn btn-outline-secondary" aria-label="Cari user"><i class="bi bi-search"></i></button>
    </div></form>
    <div class="table-responsive">
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
                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}" title="Edit" aria-label="Edit {{ $u->name }}"><i class="bi bi-pencil"></i></button>
                    @if($u->id !== auth()->id())
                    <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user {{ $u->name }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus" aria-label="Hapus {{ $u->name }}"><i class="bi bi-trash"></i></button>
                    </form>
                    @endif
                </td>
            </tr>

            @empty<tr><td colspan="7" class="text-center text-muted py-3">Belum ada user.</td></tr>@endforelse
        </tbody>
    </table>
    </div>
    {{ $users->links() }}
</div></div>

{{-- Modal harus berada di luar table-responsive agar backdrop, fokus, dan klik bekerja normal. --}}
@foreach($users as $u)
<div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-labelledby="editUserModalLabel{{ $u->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('users.update', $u) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit-user">
            <input type="hidden" name="editing_user_id" value="{{ $u->id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel{{ $u->id }}">Edit {{ $u->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="name{{ $u->id }}">Nama</label>
                    <input type="text" name="name" id="name{{ $u->id }}" class="form-control" value="{{ $editingUserId === $u->id ? old('name') : $u->name }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email{{ $u->id }}">Email</label>
                    <input type="email" name="email" id="email{{ $u->id }}" class="form-control" value="{{ $editingUserId === $u->id ? old('email') : $u->email }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password{{ $u->id }}">Password Baru</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password{{ $u->id }}" class="form-control" minlength="6" autocomplete="new-password" aria-describedby="passwordHelp{{ $u->id }}">
                        <button type="button" class="btn btn-outline-secondary password-toggle" data-target="password{{ $u->id }}" aria-label="Tampilkan password" aria-pressed="false">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div id="passwordHelp{{ $u->id }}" class="form-text">Kosongkan jika password tidak ingin diganti. Minimal 6 karakter.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="role{{ $u->id }}">Role</label>
                    <select name="role" id="role{{ $u->id }}" class="form-select" required>
                        @foreach($roles as $r)<option value="{{ $r->name }}" {{ ($editingUserId === $u->id ? old('role') === $r->name : $u->hasRole($r->name)) ? 'selected' : '' }}>{{ $r->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $u->id }}" {{ ($editingUserId === $u->id ? old('is_active') : ($u->is_active ?? true)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active{{ $u->id }}">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="addUserModal" tabindex="-1"><div class="modal-dialog"><form action="{{ route('users.store') }}" method="POST" class="modal-content">
    @csrf
    <div class="modal-header"><h5>Tambah User</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
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

@push('scripts')
<script>
    @if($editingUserId)
    document.addEventListener('DOMContentLoaded', function () {
        var failedModal = document.getElementById('editUserModal{{ $editingUserId }}');
        if (failedModal) {
            bootstrap.Modal.getOrCreateInstance(failedModal).show();
        }
    });
    @endif

    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.dataset.target);
            var showing = input.type === 'text';

            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.setAttribute('aria-label', showing ? 'Tampilkan password' : 'Sembunyikan password');
            button.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
            input.focus();
        });
    });
</script>
@endpush
