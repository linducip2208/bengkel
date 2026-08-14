<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rating Service #{{ $service->job_no }} — {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); min-height: 100vh; padding: 1.5rem; }
        .container { max-width: 560px; }
        .star { font-size: 2.6rem; color: #d1d5db; cursor: pointer; transition: all 0.15s; }
        .star.filled { color: #f59e0b; }
        .star-row .star:hover, .star-row .star:hover ~ .star { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card" style="border-radius: 18px; overflow: hidden;">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <div class="display-4 mb-2">⭐</div>
                    <h4 class="mb-1">Bagaimana Pengalaman Servis Anda?</h4>
                    <code class="small">{{ $service->job_no }}</code>
                </div>

                <div class="row mb-4">
                    <div class="col-sm-6"><small class="text-muted">Pemilik</small><div>{{ $service->customer->name ?? '-' }}</div></div>
                    <div class="col-sm-6"><small class="text-muted">Kendaraan</small><div>{{ $service->vehicle->number_plate ?? '-' }} — {{ $service->vehicle->vehicleBrand->name ?? '' }}</div></div>
                </div>

                @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
                @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif

                @if($existing)
                <div class="text-center py-3">
                    <div class="mb-2">
                        @for($i = 1; $i <= 5; $i++)<i class="bi bi-star-fill {{ $i <= $existing->rating ? 'text-warning' : 'text-light' }} fs-3"></i>@endfor
                    </div>
                    <p class="mb-0 text-muted">{{ $existing->comment ?: 'Terima kasih atas rating Anda!' }}</p>
                    <small class="text-muted">Rating Anda sedang menunggu approval admin.</small>
                </div>
                @else
                <form method="POST" action="{{ route('survey.store', $service->survey_token) }}">
                    @csrf
                    <div class="star-row d-flex justify-content-center gap-1 mb-3" id="ratingRow">
                        @for($i = 1; $i <= 5; $i++)<i class="bi bi-star-fill star" data-value="{{ $i }}"></i>@endfor
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="5" required>
                    <textarea name="comment" class="form-control mb-3" rows="3" placeholder="Komentar Anda (opsional)..."></textarea>
                    <button class="btn btn-warning w-100 btn-lg"><i class="bi bi-send me-1"></i>Kirim Rating</button>
                </form>
                @endif

                <p class="text-muted text-center small mt-4 mb-0">Link survey unik untuk service ini. Rating membantu kami meningkatkan kualitas layanan.</p>
            </div>
        </div>
    </div>

    <script>
        const stars = document.querySelectorAll('#ratingRow .star');
        const input = document.getElementById('ratingInput');
        function paint(value) { stars.forEach(s => s.classList.toggle('filled', parseInt(s.dataset.value, 10) <= value)); }
        paint(5);
        stars.forEach(s => s.addEventListener('click', () => { input.value = s.dataset.value; paint(parseInt(s.dataset.value, 10)); }));
    </script>
</body>
</html>
