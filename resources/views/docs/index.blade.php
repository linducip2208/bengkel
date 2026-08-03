@extends('docs.layout')

@section('title', 'Tutorial & Panduan Penggunaan')
@section('description', 'Tutorial lengkap penggunaan ' . $appName . ' sesuai alur bisnis bengkel: dari setup awal, pencatatan customer & kendaraan, service, inventory, sampai laporan.')
@section('crumb', 'Beranda')

@section('content')
    <h1>Tutorial {{ $appName }}</h1>
    <p class="lead">
        Panduan langkah demi langkah memakai aplikasi mengikuti alur bisnis bengkel sehari-hari.
        Mulai dari setup data master, pencatatan customer & kendaraan, proses service, inventory parts,
        invoice dan pembayaran, sampai laporan keuangan.
    </p>

    <div class="docs-callout">
        Tutorial ini cocok untuk <b>operator front-desk</b>, <b>teknisi</b>, dan <b>admin bengkel</b>.
        Ikuti bab secara berurutan jika Anda baru pertama kali memakai aplikasi.
    </div>

    <h2>Akses Login Demo</h2>
    <p>Gunakan kredensial berikut untuk mencoba aplikasi. Setelah login, segera ganti password lewat menu <b>Settings → User Profile</b>.</p>

    <div class="docs-credentials">
        <table class="docs-cred-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Email / User</th>
                    <th>Password</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><b>Admin</b></td><td><code>admin@bengkel.test</code></td><td><code>password</code></td></tr>
                <tr><td><b>Manager</b></td><td><code>manager@bengkel.test</code></td><td><code>password</code></td></tr>
                <tr><td><b>Kasir</b></td><td><code>kasir@bengkel.test</code></td><td><code>password</code></td></tr>
                <tr><td><b>Teknisi</b></td><td><code>teknisi@bengkel.test</code></td><td><code>password</code></td></tr>
                <tr><td><b>Sales</b></td><td><code>sales@bengkel.test</code></td><td><code>password</code></td></tr>
            </tbody>
        </table>
        <p class="docs-cred-note">
            URL login: <a href="{{ route('login') }}"><code>{{ url('/login') }}</code></a>
        </p>
    </div>

    <div class="docs-callout warn">
        <b>Peringatan keamanan:</b> kredensial di atas adalah <i>default</i> bawaan instalasi.
        <b>Wajib diganti</b> sebelum aplikasi dipakai di lingkungan production agar tidak terjadi akses tidak sah.
    </div>

    <h2>Alur Bisnis Bengkel</h2>
    <p>Bab-bab di bawah ini disusun mengikuti urutan kerja nyata di bengkel. Klik bab untuk membuka panduan rincinya.</p>

    <div class="docs-card-grid">
        @foreach($sections as $s)
            <a href="{{ route('docs.show', $s['slug']) }}" class="docs-card">
                <div class="icon"><i class="fas {{ $s['icon'] }}"></i></div>
                <div class="t">{{ $s['title'] }}</div>
                <div class="d">{{ $s['lead'] }}</div>
            </a>
        @endforeach
    </div>

    <h2>Tip Memulai</h2>
    <ol>
        <li>Login dengan akun admin yang diberikan.</li>
        <li>Buka <b>Settings</b> → isi profil bengkel, mata uang, dan zona waktu.</li>
        <li>Lengkapi <b>Master Data</b> sebelum operasional dimulai.</li>
        <li>Mulai daftarkan <b>Customer</b> + <b>Kendaraan</b>, lalu buat <b>Service</b> pertama.</li>
        <li>Pakai <b>Reports</b> di akhir hari untuk evaluasi penjualan & stok.</li>
    </ol>

    <div class="docs-callout tip">
        Butuh bantuan teknis? Hubungi tim support yang memberikan instalasi aplikasi. Tutorial ini juga bisa
        di-share ke karyawan baru sebagai materi onboarding — cukup kirim link <code>/docs</code>.
    </div>
@endsection
