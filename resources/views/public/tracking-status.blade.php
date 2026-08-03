<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Track Service #{{ $service->job_no }} — {{ config('app.name', 'Bengkel Paten') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="noindex">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); min-height: 100vh; padding: 1.5rem; }
        .container { max-width: 720px; }
        .timeline { position: relative; padding-left: 2rem; }
        .timeline::before { content: ''; position: absolute; left: 11px; top: 0; bottom: 0; width: 2px; background: #e5e7eb; }
        .step { position: relative; padding: 0.5rem 0 1rem 1rem; }
        .step::before { content: ''; position: absolute; left: -1.4rem; top: 0.7rem; width: 22px; height: 22px; border-radius: 50%; background: #e5e7eb; border: 3px solid #fff; }
        .step.done::before { background: #10b981; }
        .step.active::before { background: #f59e0b; animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.5); } 50% { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); } }
        .step .label { font-weight: 600; color: #1f2937; }
        .step.pending .label { color: #9ca3af; }
        .star { font-size: 2rem; color: #d1d5db; cursor: pointer; transition: all 0.15s; }
        .star.filled, .star:hover, .star:hover ~ .star { color: #d1d5db; }
        .star.filled { color: #f59e0b; }
        .star-row:hover .star { color: #f59e0b; }
        .star-row .star:hover ~ .star { color: #d1d5db; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card" style="border-radius: 18px; overflow: hidden;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="mb-0">Status Service</h4>
                        <code class="small">{{ $service->job_no }}</code>
                    </div>
                    <a href="https://wa.me/6281296052010" class="btn btn-sm btn-outline-success"><i class="bi bi-whatsapp me-1"></i>Hubungi</a>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6"><small class="text-muted">Pemilik</small><div>{{ $service->customer->name ?? '-' }}</div></div>
                    <div class="col-sm-6"><small class="text-muted">Kendaraan</small><div>{{ $service->vehicle->number_plate ?? '-' }} — {{ $service->vehicle->vehicleBrand->name ?? '' }} {{ $service->vehicle->model_name ?? '' }}</div></div>
                    <div class="col-sm-6 mt-2"><small class="text-muted">Tanggal Masuk</small><div>{{ $service->service_date->format('d M Y H:i') }}</div></div>
                    <div class="col-sm-6 mt-2"><small class="text-muted">Keluhan</small><div>{{ $service->description ?: '-' }}</div></div>
                </div>

                <h6 class="mt-4 mb-3"><i class="bi bi-list-check me-1"></i>Progress</h6>
                @php
                    $status = (int) $service->done_status;
                    $stages = [
                        ['label' => 'Kendaraan Masuk', 'done' => true],
                        ['label' => 'Inspeksi & Observasi', 'done' => $service->serviceObservationPoints->count() > 0, 'active' => $status === 0],
                        ['label' => 'Dikerjakan Teknisi', 'done' => $status >= 1, 'active' => $status === 1],
                        ['label' => 'Selesai & Siap Diambil', 'done' => $status >= 2, 'active' => false],
                        ['label' => 'Invoice & Pembayaran', 'done' => $service->invoice && (int) $service->invoice->payment_status === 2],
                    ];
                @endphp
                <div class="timeline">
                    @foreach($stages as $stage)
                    <div class="step {{ ($stage['active'] ?? false) ? 'active' : ($stage['done'] ? 'done' : 'pending') }}">
                        <div class="label">
                            {{ $stage['label'] }}
                            @if($stage['active'] ?? false)<span class="badge bg-warning text-dark ms-1">Sedang berjalan</span>@elseif($stage['done'])<i class="bi bi-check-circle-fill text-success ms-1"></i>@endif
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($service->invoice)
                <div class="alert alert-info mt-3">
                    <strong>Invoice:</strong> {{ $service->invoice->invoice_number }}<br>
                    <strong>Total:</strong> Rp {{ number_format($service->invoice->grand_total, 0, ',', '.') }}<br>
                    <strong>Status:</strong>
                    @if((int) $service->invoice->payment_status === 2)<span class="badge bg-success">LUNAS</span>
                    @elseif((int) $service->invoice->payment_status === 1)<span class="badge bg-warning text-dark">CICIL</span>
                    @else<span class="badge bg-danger">BELUM BAYAR</span>@endif
                </div>
                @endif

                @if($status >= 2)
                <hr>
                <h6 class="mt-4 mb-2"><i class="bi bi-star me-1"></i>Beri Review Service Ini</h6>
                @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

                @if($review && $review->is_published)
                <div class="border rounded p-3 bg-light">
                    <div>
                        @for($i = 1; $i <= 5; $i++)<i class="bi bi-star-fill {{ $i <= $review->rating ? 'text-warning' : 'text-light' }}"></i>@endfor
                        <strong class="ms-2">{{ $review->reviewer_name }}</strong>
                    </div>
                    <p class="mb-0 small">{{ $review->comment }}</p>
                    @if($review->admin_reply)<div class="mt-2 ps-3 border-start"><small class="text-muted"><strong>Reply Admin:</strong> {{ $review->admin_reply }}</small></div>@endif
                </div>
                @elseif($review && !$review->is_published)
                <div class="alert alert-secondary small"><i class="bi bi-clock me-1"></i>Review Anda sedang menunggu approval admin.</div>
                @else
                <form method="POST" action="{{ route('public.tracking.review', $service->tracking_token) }}">
                    @csrf
                    <input type="text" name="reviewer_name" class="form-control mb-2" placeholder="Nama Anda" value="{{ $service->customer->name ?? '' }}" required>
                    <div class="star-row d-flex gap-1 mb-2" id="ratingRow">
                        @for($i = 1; $i <= 5; $i++)<i class="bi bi-star-fill star" data-value="{{ $i }}"></i>@endfor
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="5" required>
                    <textarea name="comment" class="form-control mb-2" rows="3" placeholder="Pengalaman service Anda..."></textarea>
                    <button class="btn btn-warning"><i class="bi bi-send me-1"></i>Kirim Review</button>
                </form>
                <script>
                    const stars = document.querySelectorAll('#ratingRow .star');
                    const input = document.getElementById('ratingInput');
                    function paint(value) { stars.forEach(s => s.classList.toggle('filled', parseInt(s.dataset.value, 10) <= value)); }
                    paint(5);
                    stars.forEach(s => s.addEventListener('click', () => { input.value = s.dataset.value; paint(parseInt(s.dataset.value, 10)); }));
                </script>
                @endif
                @endif

                <p class="text-muted text-center small mt-4 mb-0">Link tracking unik untuk service ini. Simpan halaman ini untuk pantau status real-time.</p>
            </div>
        </div>
    </div>
</body>
</html>
