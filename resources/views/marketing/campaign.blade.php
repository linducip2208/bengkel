@extends('layouts.app')
@section('title', 'Bulk Campaign')
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
@endpush
@section('content')
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('marketing.campaign.send') }}">@csrf
<div class="row g-3 mb-3">
    <div class="col-md-3"><label>Channel *</label><select name="channel" class="form-select" required><option value="whatsapp">WhatsApp</option><option value="sms">SMS</option></select></div>
    <div class="col-md-9"><label>Pilih Customer *</label><select name="customer_ids[]" id="customerSelect" class="form-select" multiple required></select></div>
</div>
<div class="mb-3"><label>Pesan *</label><textarea name="message" rows="5" class="form-control" required placeholder="Halo {nama}, dapatkan promo spesial..."></textarea><small class="text-muted">Max 1600 karakter. Gunakan {nama} untuk nama customer.</small></div>
<button class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Kirim Campaign</button>
</form></div></div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
<script>
$('#customerSelect').select2({theme:'bootstrap-5',placeholder:'Cari customer...',ajax:{url:'{{ route("marketing.campaign.search") }}',dataType:'json',delay:300,data:p=>({q:p.term}),processResults:d=>({results:d.map(c=>({id:c.id,text:c.name+' ('+c.phone+')'}))})}});
</script>
@endpush
@endsection
