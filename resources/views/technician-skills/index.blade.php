@extends('layouts.app')
@section('title', 'Skill Matrix Teknisi')

@section('content')
@php
$levelColor = ['basic' => 'secondary', 'intermediate' => 'info', 'expert' => 'success'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Skill Matrix Teknisi</h4>
</div>

{{-- Tambah skill --}}
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-plus-circle me-1"></i>Tambah Skill Teknisi</h6></div>
    <div class="card-body">
        <form action="{{ route('technician-skills.store') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label small mb-1">Teknisi</label>
                <select name="user_id" class="form-select form-select-sm" required>
                    <option value="">Pilih Teknisi</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Skill</label>
                <select name="skill" class="form-select form-select-sm" required>
                    <option value="">Pilih Skill</option>
                    @foreach($skills as $skill)
                        <option value="{{ $skill }}">{{ $skill }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Level</label>
                <select name="level" class="form-select form-select-sm">
                    @foreach($levels as $level)
                        <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Catatan</label>
                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opsional">
            </div>
            <div class="col-md-1">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg"></i></button>
            </div>
        </form>
    </div>
</div>

{{-- Matrix --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:220px">Teknisi</th>
                    <th>Skills</th>
                </tr>
            </thead>
            <tbody>
                @forelse($technicians as $tech)
                <tr>
                    <td>
                        <strong>{{ $tech->name }}</strong><br>
                        <small class="text-muted">{{ $tech->email }}</small>
                    </td>
                    <td>
                        @forelse($tech->skills as $skill)
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="badge bg-{{ $levelColor[$skill->level] ?? 'secondary' }}">{{ $skill->skill }}</span>
                            <form action="{{ route('technician-skills.update', $skill) }}" method="POST" class="d-flex gap-1 align-items-center">
                                @csrf
                                @method('PUT')
                                <select name="level" class="form-select form-select-sm" style="width:auto">
                                    @foreach($levels as $level)
                                        <option value="{{ $level }}" {{ $skill->level === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="notes" value="{{ $skill->notes }}" class="form-control form-control-sm" placeholder="Catatan" style="width:130px">
                                <button class="btn btn-sm btn-outline-primary" title="Simpan"><i class="bi bi-check"></i></button>
                            </form>
                            <form action="{{ route('technician-skills.destroy', $skill) }}" method="POST" onsubmit="return confirm('Hapus skill ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-x"></i></button>
                            </form>
                        </div>
                        @empty
                        <span class="text-muted small">Belum ada skill</span>
                        @endforelse
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" class="text-center text-muted py-4">Tidak ada teknisi terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
