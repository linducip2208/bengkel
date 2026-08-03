{{--
    Usage:
    @include('partials.confirm-delete', [
        'id' => 'deleteModal',
        'title' => 'Hapus Pelanggan?',
        'message' => 'Data ini tidak dapat dikembalikan.',
        'action' => route('customers.destroy', $customer->id)
    ])
--}}

@php
    $modalId = $id ?? 'confirmDeleteModal';
    $modalTitle = $title ?? 'Konfirmasi Hapus';
    $modalMessage = $message ?? 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
    $modalAction = $action ?? '#';
    $btnText = $btnText ?? 'Hapus';
    $itemName = $itemName ?? '';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-danger">
                    <i class="fas fa-trash-alt me-2"></i>{{ $modalTitle }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ $modalMessage }}</p>
                @if($itemName)
                    <p class="fw-semibold text-center text-muted">{{ $itemName }}</p>
                @endif
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="{{ $modalAction }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> {{ $btnText }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
