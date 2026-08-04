<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Service — {{ config('app.name', 'Aplikasi Bengkel Terbaik') }}</title>
    <meta name="description" content="Booking service kendaraan online — pilih cabang, jam, dan layanan.">
    <link rel="canonical" href="{{ url()->current() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f97316 0%, #ec4899 50%, #8b5cf6 100%); min-height: 100vh; padding: 2rem 1rem; }
        .booking-card { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
        .booking-header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: #fff; padding: 1.5rem 2rem; }
        .booking-body { padding: 2rem; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25); }
    </style>
</head>
<body>
    <div class="booking-card">
        <div class="booking-header">
            <h3 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Booking Service Online</h3>
            <small>Pesan jadwal service kendaraan Anda. Tim kami akan konfirmasi via WA.</small>
        </div>
        <div class="booking-body">
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('public.booking.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Cabang</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Cabang manapun</option>
                            @foreach($branches as $b)<option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal & Jam <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="booking_at" class="form-control" value="{{ old('booking_at') }}" required min="{{ now()->addHour()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required placeholder="08123456789">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email (opsional)</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Plat Nomor</label>
                        <input type="text" name="vehicle_plate" class="form-control" value="{{ old('vehicle_plate') }}" placeholder="B 1234 ABC">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Merek</label>
                        <input type="text" name="vehicle_brand" class="form-control" value="{{ old('vehicle_brand') }}" placeholder="Toyota">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Model</label>
                        <input type="text" name="vehicle_model" class="form-control" value="{{ old('vehicle_model') }}" placeholder="Avanza 2020">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keluhan / Service yang Diinginkan</label>
                        <textarea name="complaint" class="form-control" rows="3" placeholder="Mis: ganti oli + tune up, AC kurang dingin">{{ old('complaint') }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mt-4"><i class="bi bi-send me-2"></i>Kirim Booking</button>
            </form>
            <p class="text-muted small mt-3 text-center mb-0">
                Sudah jadi customer? <a href="{{ route('customer.login') }}">Login Customer Portal</a> untuk lihat history.
            </p>
        </div>
    </div>
</body>
</html>
