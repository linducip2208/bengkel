@extends('layouts.app')
@section('title', 'Role & Permission')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-shield-lock me-2"></i>Role & Permission</h4>
    <div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-people me-1"></i>Daftar User</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal"><i class="bi bi-plus-lg me-1"></i>Tambah Role</button>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Cara pakai cepat:</strong> klik tombol <em>Quick Set</em> di pojok kanan tiap role untuk preset umum (View Only / Read+Edit / Full Access), atau centang header <em>kategori</em> untuk pilih semua action dalam 1 modul sekaligus. Tombol <strong>Centang Semua</strong> untuk grant penuh dalam 1 klik.
</div>

@foreach($roles as $role)
@php $isSystem = in_array($role->name, ['super_admin', 'admin']); @endphp
<div class="card mb-3" data-role-card="{{ $role->id }}">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="bi bi-shield-fill text-{{ $role->name === 'super_admin' ? 'danger' : ($role->name === 'admin' ? 'warning' : 'primary') }} me-2"></i>
                {{ $role->name }}
                <small class="text-muted ms-2">{{ $role->users_count }} user • <span class="perm-count">{{ $role->permissions->count() }}</span> permission</small>
            </h5>
            @if(!$isSystem)
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-lightning-fill me-1"></i>Quick Set
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-preset="all" data-role-id="{{ $role->id }}"><i class="bi bi-check-all text-success me-2"></i>Full Access (semua)</a></li>
                    <li><a class="dropdown-item" href="#" data-preset="view" data-role-id="{{ $role->id }}"><i class="bi bi-eye me-2"></i>View Only</a></li>
                    <li><a class="dropdown-item" href="#" data-preset="read_edit" data-role-id="{{ $role->id }}"><i class="bi bi-pencil me-2"></i>Read + Edit (no delete)</a></li>
                    <li><a class="dropdown-item" href="#" data-preset="create_read" data-role-id="{{ $role->id }}"><i class="bi bi-plus-circle me-2"></i>Create + Read</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" data-preset="none" data-role-id="{{ $role->id }}"><i class="bi bi-x-circle me-2"></i>Clear Semua</a></li>
                </ul>
            </div>
            @endif
        </div>

        <form action="{{ route('roles.update', $role) }}" method="POST" data-role-form="{{ $role->id }}">
            @csrf @method('PUT')

            @if(!$isSystem)
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <button type="button" class="btn btn-sm btn-success" data-role-check-all="{{ $role->id }}">
                    <i class="bi bi-check-all me-1"></i>Centang Semua
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-role-uncheck-all="{{ $role->id }}">
                    <i class="bi bi-x me-1"></i>Hapus Semua Centang
                </button>
                <span class="vr"></span>
                <button type="button" class="btn btn-sm btn-outline-info" data-role-toggle-action="{{ $role->id }}" data-action="view">View semua modul</button>
                <button type="button" class="btn btn-sm btn-outline-info" data-role-toggle-action="{{ $role->id }}" data-action="create">Create semua modul</button>
                <button type="button" class="btn btn-sm btn-outline-info" data-role-toggle-action="{{ $role->id }}" data-action="edit">Edit semua modul</button>
                <button type="button" class="btn btn-sm btn-outline-info" data-role-toggle-action="{{ $role->id }}" data-action="delete">Delete semua modul</button>
            </div>
            @endif

            <div class="row">
                @foreach($permissions as $module => $perms)
                <div class="col-md-3 col-sm-6 mb-3 module-block" data-module="{{ $module }}">
                    <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                        <h6 class="text-muted text-uppercase small mb-0">
                            <i class="bi bi-folder me-1"></i>{{ $module }}
                        </h6>
                        @if(!$isSystem)
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input module-check-all" type="checkbox"
                                data-role-id="{{ $role->id }}" data-module="{{ $module }}"
                                id="modAll{{ $role->id }}_{{ $module }}"
                                title="Centang semua action di {{ $module }}">
                            <label class="form-check-label small text-primary fw-bold" for="modAll{{ $role->id }}_{{ $module }}">all</label>
                        </div>
                        @endif
                    </div>
                    <div class="ms-1">
                    @foreach($perms as $perm)
                    @php $action = str_replace($module . '.', '', $perm->name); @endphp
                    <div class="form-check">
                        <input class="form-check-input perm-checkbox" type="checkbox"
                            name="permissions[]" value="{{ $perm->name }}"
                            data-role-id="{{ $role->id }}" data-module="{{ $module }}" data-action="{{ $action }}"
                            id="p{{ $role->id }}_{{ $perm->id }}"
                            {{ $role->permissions->contains('id', $perm->id) ? 'checked' : '' }}
                            {{ $isSystem ? 'disabled' : '' }}>
                        <label class="form-check-label small" for="p{{ $role->id }}_{{ $perm->id }}">{{ $action }}</label>
                    </div>
                    @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            @if(!$isSystem)
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Permission untuk {{ $role->name }}</button>
            @else
            <small class="text-muted"><em><i class="bi bi-info-circle me-1"></i>Role sistem ({{ $role->name }}) otomatis dapat semua permission, tidak bisa diubah & tidak bisa dihapus.</em></small>
            @endif
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="addRoleModal" tabindex="-1"><div class="modal-dialog"><form action="{{ route('roles.store') }}" method="POST" class="modal-content">
    @csrf
    <div class="modal-header"><h5>Tambah Role Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Nama Role <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="mis: warehouse, supervisor, frontdesk" required>
        </div>
        <p class="text-muted small mb-0">Setelah role dibuat, permission bisa di-set langsung dengan tombol "Centang Semua" atau preset Quick Set.</p>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-success">Buat Role</button></div>
</form></div></div>

<script>
(function() {
    function recountRole(roleId) {
        const checks = document.querySelectorAll(`[data-role-card="${roleId}"] .perm-checkbox`);
        const checked = Array.from(checks).filter(c => c.checked).length;
        const el = document.querySelector(`[data-role-card="${roleId}"] .perm-count`);
        if (el) el.textContent = checked;
    }
    function syncMasters(roleId) {
        document.querySelectorAll(`.module-check-all[data-role-id="${roleId}"]`).forEach(master => {
            const module = master.dataset.module;
            const all = document.querySelectorAll(`.perm-checkbox[data-role-id="${roleId}"][data-module="${module}"]`);
            master.checked = all.length > 0 && Array.from(all).every(c => c.checked);
        });
    }

    document.querySelectorAll('.module-check-all').forEach(masterBox => {
        masterBox.addEventListener('change', function() {
            const roleId = this.dataset.roleId;
            const module = this.dataset.module;
            document.querySelectorAll(`.perm-checkbox[data-role-id="${roleId}"][data-module="${module}"]`).forEach(cb => {
                if (!cb.disabled) cb.checked = this.checked;
            });
            recountRole(roleId);
        });
    });

    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            syncMasters(this.dataset.roleId);
            recountRole(this.dataset.roleId);
        });
    });

    document.querySelectorAll('[data-role-check-all]').forEach(btn => {
        btn.addEventListener('click', function() {
            const roleId = this.dataset.roleCheckAll;
            document.querySelectorAll(`[data-role-card="${roleId}"] .perm-checkbox`).forEach(cb => { if (!cb.disabled) cb.checked = true; });
            syncMasters(roleId);
            recountRole(roleId);
        });
    });
    document.querySelectorAll('[data-role-uncheck-all]').forEach(btn => {
        btn.addEventListener('click', function() {
            const roleId = this.dataset.roleUncheckAll;
            document.querySelectorAll(`[data-role-card="${roleId}"] .perm-checkbox`).forEach(cb => { if (!cb.disabled) cb.checked = false; });
            syncMasters(roleId);
            recountRole(roleId);
        });
    });

    document.querySelectorAll('[data-role-toggle-action]').forEach(btn => {
        btn.addEventListener('click', function() {
            const roleId = this.dataset.roleToggleAction;
            const action = this.dataset.action;
            const checks = document.querySelectorAll(`.perm-checkbox[data-role-id="${roleId}"][data-action="${action}"]`);
            const allChecked = Array.from(checks).every(c => c.checked);
            checks.forEach(cb => { if (!cb.disabled) cb.checked = !allChecked; });
            syncMasters(roleId);
            recountRole(roleId);
        });
    });

    document.querySelectorAll('[data-preset]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const roleId = this.dataset.roleId;
            const allowed = {
                'all': ['view', 'create', 'edit', 'delete'],
                'view': ['view'],
                'read_edit': ['view', 'edit'],
                'create_read': ['view', 'create'],
                'none': [],
            }[this.dataset.preset] || [];
            document.querySelectorAll(`.perm-checkbox[data-role-id="${roleId}"]`).forEach(cb => {
                if (!cb.disabled) cb.checked = allowed.includes(cb.dataset.action);
            });
            syncMasters(roleId);
            recountRole(roleId);
        });
    });

    // Init sync
    document.querySelectorAll('.module-check-all').forEach(master => {
        const roleId = master.dataset.roleId;
        const module = master.dataset.module;
        const all = document.querySelectorAll(`.perm-checkbox[data-role-id="${roleId}"][data-module="${module}"]`);
        master.checked = all.length > 0 && Array.from(all).every(c => c.checked);
    });
})();
</script>
@endsection
