@extends('layouts.app')

@section('title', 'Checklist Observasi')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-tasks text-danger me-2"></i>Checklist Observasi: {{ $service->job_no }}</span>
                <a href="{{ route('services.show', $service) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('observations.save-checklist', $service) }}" method="POST">
                    @csrf

                    @foreach($groupedPoints as $type => $points)
                    <h6 class="border-bottom pb-2 mt-3">{{ $type }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width:50%;">Poin Pemeriksaan</th>
                                    <th style="width:120px;" class="text-center">Status</th>
                                    <th>Komentar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($points as $point)
                                @php $result = $checkResults->get($point->id); @endphp
                                <tr>
                                    <td>{{ $point->observation_point }}</td>
                                    <td class="text-center">
                                        <input type="hidden" name="points[{{ $point->id }}][is_checked]" value="0">
                                        <input type="checkbox" name="points[{{ $point->id }}][is_checked]" value="1"
                                               class="form-check-input"
                                               {{ ($result && $result->checked) ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <input type="text" name="points[{{ $point->id }}][comment]"
                                               class="form-control form-control-sm"
                                               value="{{ $result->comment ?? '' }}"
                                               placeholder="Komentar...">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endforeach

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('services.show', $service) }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save me-1"></i> Simpan Checklist
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
