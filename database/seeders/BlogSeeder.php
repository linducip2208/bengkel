<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first()
            ?? User::orderBy('id')->first();

        $categories = collect([
            ['name' => 'Tips Perawatan', 'slug' => 'tips-perawatan', 'description' => 'Panduan perawatan rutin agar kendaraan awet dan performa tetap prima.'],
            ['name' => 'Berita Otomotif', 'slug' => 'berita-otomotif', 'description' => 'Kabar terbaru dunia otomotif nasional dan internasional.'],
            ['name' => 'Manajemen Bengkel', 'slug' => 'manajemen-bengkel', 'description' => 'Strategi mengelola bengkel modern: operasional, keuangan, dan pelayanan customer.'],
            ['name' => 'Sparepart & Oli', 'slug' => 'sparepart-oli', 'description' => 'Panduan memilih sparepart, oli, dan komponen kendaraan yang tepat.'],
            ['name' => 'Digitalisasi Bengkel', 'slug' => 'digitalisasi-bengkel', 'description' => 'Transformasi digital bengkel: POS, invoice, booking online, dan automation.'],
        ])->mapWithKeys(function ($c) {
            $cat = BlogCategory::updateOrCreate(['slug' => $c['slug']], $c);

            return [$c['slug'] => $cat];
        });

        $posts = [
            // ── Tips Perawatan ──
            ['category' => 'tips-perawatan', 'title' => '7 Perawatan Rutin Mobil Agar Tetap Awet di Jalan Indonesia', 'slug' => 'perawatan-rutin-mobil-awet',
                'excerpt' => 'Jalan berlubang, macet, dan cuaca tropis membuat mobil di Indonesia butuh perhatian ekstra. Ini 7 perawatan rutin yang wajib Anda lakukan.',
                'body' => '<p>Mobil yang dipakai harian di jalan Indonesia menghadapi tantangan unik: jalan berlubang, kemacetan panjang, kelembaban tinggi, dan debu. Tanpa perawatan rutin, kondisi ini mempercepat kerusakan komponen penting.</p><h3>1. Ganti Oli Mesin Tepat Waktu</h3><p>Oli adalah darah mesin. Di iklim tropis, oli lebih cepat terdegradasi karena suhu tinggi. Ganti setiap 5.000 km untuk oli mineral atau 10.000 km untuk oli sintetis.</p><h3>2. Cek Radiator dan Coolant</h3><p>Macet parah membuat suhu mesin mudah naik. Pastikan level coolant selalu cukup dan radiator dibersihkan setiap 20.000 km.</p><h3>3. Rotasi dan Balancing Ban</h3><p>Lakukan rotasi ban setiap 10.000 km agar keausan merata. Balancing dan spooring menjaga kenyamanan serta mencegah ban cepat botak.</p><h3>4. Ganti Filter Udara Kabin</h3><p>Filter udara kabin yang kotor membuat AC lemah dan berbau. Ganti setiap 15.000 km atau saat AC mulai tidak dingin.</p><h3>5. Periksa Kampas Rem</h3><p>Rem adalah sistem keselamatan utama. Cek ketebalan kampas setiap 10.000 km — terutama jika sering melewati jalanan macet.</p><h3>6. Cek Aki dan Kelistrikan</h3><p>Aki rata-rata usia 1,5–2 tahun. Tes tegangan aki secara berkala, bersihkan terminal dari korosi.</p><h3>7. Tune-Up Berkala</h3><p>Tune-up setiap 20.000 km memastikan busi, filter, dan sistem bahan bakar bekerja optimal sehingga konsumsi BBM tetap efisien.</p><p>Dengan aplikasi bengkel modern, riwayat service kendaraan Anda tercatat otomatis — pengingat service berikutnya pun datang tepat waktu via WhatsApp.</p>'],
            ['category' => 'tips-perawatan', 'title' => 'Cara Merawat Motor Matic Supaya Tidak Ngadat', 'slug' => 'merawat-motor-matic',
                'excerpt' => 'Motor matic praktis tapi rawan rusak kalau salah perawatan. Ikuti panduan lengkap dari CVT sampai baterai.',
                'body' => '<p>Motor matic menjadi andalan mobilitas sehari-hari di Indonesia. Namun transmisi otomatis (CVT) punya karakter perawatan tersendiri yang sering diabaikan pemiliknya.</p><h3>Servis CVT Setiap 8.000 Km</h3><p>Roller, belt, dan kampas kopling CVT aus seiring pemakaian. Belt yang sudah melar membuat tarikan berat dan konsumsi boros. Ganti belt setiap 24.000 km, roller dan kampas sesuai rekomendasi teknisi.</p><h3>Ganti Oli Mesin & Gardan</h3><p>Oli mesin matic ganti tiap 2.000 km, sedangkan oli gardan (final drive) tiap 8.000 km. Banyak pemilik lupa oli gardan hingga gear berbunyi.</p><h3>Jangan Beban Berlebih</h3><p>Membawa beban melebihi kapasitas memaksa CVT bekerja ekstra keras — umur belt dan roller cepat habis.</p><h3>Cek Baterai & Aki Kering</h3><p>Motor matic injeksi sangat bergantung pada kelistrikan. Aki lemah membuat motor sulit distarter bahkan mati mendadak.</p><h3>Pilih Bengkel Terpercaya</h3><p>Gunakan bengkel dengan pencatatan service digital supaya riwayat perawatan motor Anda terdokumentasi rapi dan tidak ada servis yang terlewat.</p>'],

            // ── Manajemen Bengkel ──
            ['category' => 'manajemen-bengkel', 'title' => 'Cara Mengelola Keuangan Bengkel Agar Profit Konsisten', 'slug' => 'mengelola-keuangan-bengkel',
                'excerpt' => 'Bengkel ramai tapi uang kas selalu tipis? Mungkin pembukuan Anda belum rapi. Ini cara menyusun sistem keuangan bengkel yang sehat.',
                'body' => '<p>Banyak pemilik bengkel merasa usahanya untung karena bengkel ramai setiap hari. Tetapi tanpa pembukuan yang benar, laba bisa bocor tanpa disadari: parts hilang, piutang tertunggak, dan pengeluaran tak tercatat.</p><h3>Pisahkan Uang Pribadi dan Usaha</h3><p>Kesalahan paling dasar: mencampur kas pribadi dengan kas bengkel. Sediakan rekening khusus usaha dan gaji diri sendiri secara rutin.</p><h3>Catat Setiap Transaksi Real-Time</h3><p>Setiap penjualan jasa maupun parts harus langsung masuk sistem. Dengan POS bengkel digital, kasir mencatat transaksi dalam hitungan detik lengkap dengan stok yang terpotong otomatis.</p><h3>Pantau Piutang Fleet dan Korporat</h3><p>Pelanggan fleet biasanya membayar tempo. Gunakan laporan AR aging untuk memantau invoice mana yang jatuh tempo dan siapa yang harus ditagih.</p><h3>Analisis Margin per Layanan</h3><p>Tahu tidak layanan mana yang paling menguntungkan? Service rutin biasanya margin tipis tapi volume tinggi; overhaul margin tebal tapi jarang. Data ini menentukan strategi promosi Anda.</p><h3>Review Laporan Mingguan</h3><p>Alokasikan 30 menit tiap minggu untuk melihat: omzet, laba kotor, pengeluaran besar, dan piutang. Aplikasi ERP bengkel bisa mengirim ringkasan mingguan otomatis ke WhatsApp Anda.</p>'],
            ['category' => 'manajemen-bengkel', 'title' => 'Struktur Komisi Teknisi yang Adil dan Memotivasi', 'slug' => 'struktur-komisi-teknisi',
                'excerpt' => 'Skema komisi yang salah bikin teknisi saling rebutan job. Begini cara menyusun insentif yang adil sekaligus mendorong kualitas kerja.',
                'body' => '<p>Komisi teknisi adalah senjata dua mata: terlalu kecil membuat motivasi turun, terlalu besar merusak laba. Lebih buruk lagi, skema yang salah bisa memicu teknisi hanya memilih job "menguntungkan".</p><h3>Hitung Berdasarkan Jam Kerja Efektif</h3><p>Dasar komisi terbaik adalah jam kerja efektif per job — bukan nilai invoice. Dengan timer digital per teknisi, durasi pengerjaan terekam otomatis dan objektif.</p><h3>Bobot Berdasarkan Skill Level</h3><p>Teknisi junior dapat persentase dasar, senior lebih tinggi. Skill matrix membantu owner memetakan kompetensi: siapa ahli mesin, siapa ahli kelistrikan, siapa layak handle job warranty.</p><h3>Bonus Kualitas, Bukan Sekadar Volume</h3><p>Beri bonus jika pekerjaan lolos quality control tanpa rework, dan customer memberi rating bagus. Ini mencegah kerja asal cepat.</p><h3>Transparansi Itu Wajib</h3><p>Teknisi harus bisa melihat akumulasi komisinya kapan saja. Dashboard komisi per periode menghilangkan gesekan gosip "hitungan owner nggak transparan" dan mempermudah pembayaran payroll.</p>'],

            // ── Digitalisasi Bengkel ──
            ['category' => 'digitalisasi-bengkel', 'title' => '5 Tanda Bengkel Anda Butuh Sistem Digital Sekarang', 'slug' => 'tanda-bengkel-butuh-sistem-digital',
                'excerpt' => 'Masih catat service di buku tulis? Lima tanda ini menunjukkan bengkel Anda mulai kalah bersaing tanpa sistem digital.',
                'body' => '<p>Industri bengkel Indonesia sedang bertransformasi. Bengkel yang masih manual akan tertinggal dalam kecepatan layanan dan akurasi data. Kenali lima tandanya:</p><h3>1. Buku Besar Sering Hilang atau Rusak</h3><p>Data service di buku tulis rawan hilang, rusak kena air, dan mustahil dicari ulang dengan cepat. Saat customer bertanya "kapan terakhir ganti kampas rem?", petugas muter-muter cari arsip.</p><h3>2. Stok Parts Tidak Pernah Cocok</h3><p>Stok opname selalu selisih? Tanpa sistem inventory terintegrasi, parts keluar tanpa tercatat — entah hilang atau "terlupakan".</p><h3>3. Customer Serius Kabur ke Bengkel Lain</h3><p>Tanpa database customer dan reminder otomatis, customer lupa kembali service ke tempat Anda. Kompetitor yang mengirim reminder WhatsApp H-7 akan lebih dulu mendapatkannya.</p><h3>4. Rekap Omzet Butuh Berhari-hari</h3><p>Owner butuh laporan bulanan dan staf butuh seminggu merekap manual. Padahal dashboard real-time bisa menampilkan omzet hari ini dalam sekali klik.</p><h3>5. Antrean Booking Kacau Saat Ramai</h3><p>Weekend ramai, telepon bunyi terus, booking dicat di kertas — hasilnya double booking dan customer kecewa. Booking online dengan kalender slot membuat antrean tertata otomatis.</p><p>Kabar baiknya: migrasi ke sistem digital tidak perlu mahal. ERP bengkel berbasis web bisa langsung dipakai tanpa instalasi rumit, lengkap dari check-in sampai invoice PDF.</p>'],
            ['category' => 'digitalisasi-bengkel', 'title' => 'Booking Online Bengkel: Naikkan Okupansi Slot Servis 40%', 'slug' => 'booking-online-bengkel',
                'excerpt' => 'Slot servis yang kosong di hari kerja itu rugi. Booking online memastikan bengkel Anda ramai merata sepanjang minggu.',
                'body' => '<p>Kapasitas bengkel adalah aset yang hangus jika tidak terpakai. Satu lift kosong satu jam sama saja dengan pendapatan yang menguap. Di sinilah booking online berperan besar.</p><h3>Mengapa Booking Online Efektif?</h3><p>Mayoritas customer memilih menghindari antre. Dengan link booking yang disebar di Google Maps, WhatsApp, dan Instagram, mereka bisa pilih sendiri slot yang tersedia — termasuk slot sepi di hari Selasa-Kamis yang biasanya kosong.</p><h3>Otomatisasi Jadwal Teknisi</h3><p>Sistem booking modern tidak hanya mencatat jadwal, tapi juga mempertimbangkan kapasitas lift dan jumlah teknisi yang bertugas. Tidak ada lagi overbook yang membuat customer menunggu berjam-jam.</p><h3>Konversi Satu Klik</h3><p>Booking yang masuk langsung tampil di dashboard admin. Front-desk tinggal klik "convert" saat customer datang — data customer, kendaraan, dan keluhan sudah lengkap tanpa input ulang.</p><h3>Reminder Mengurangi No-Show</h3><p>Notifikasi otomatis H-1 dan H-3 sebelum jadwal menurunkan no-show drastis. Customer yang berhalangan bisa reschedule, slotnya langsung terbuka untuk orang lain.</p>'],

            // ── Sparepart & Oli ──
            ['category' => 'sparepart-oli', 'title' => 'Oli Original vs Tiruran: Cara Membedakan yang Akurat', 'slug' => 'oli-original-vs-tiruran',
                'excerpt' => 'Oli palsu bisa merusak mesin dalam hitungan ribu kilometer. Pelajari cara membedakan oli original dan tiruran sebelum terlanjur.',
                'body' => '<p>Peredaran oli tiruran di Indonesia sangat masif. Modusnya makin canggih: kemasan mirip, hologram palsu, bahkan segel yang tampak asli. Kerugiannya bukan main — oli palsu melapisi komponen mesin dengan buruk dan memicu kerusakan dini.</p><h3>Cek Hologram dan QR Code Resmi</h3><p>Hampir semua brand besar menyediakan verifikasi via QR code atau aplikasi resmi. Scan sebelum beli — bukan setelah dibuka.</p><h3>Perhatikan Kualitas Kemasan</h3><p>Original: plastik jernih, cetakan label tajam, tutup rapat tanpa bekas obek. Palsu: warna label pudar, font tidak presisi, kadang ada gelembung udara di label.</p><h3>Beli di Distributor Resmi</h3><p>Bengkel resmi dan toko parts besar mendapat pasokan dari distributor terverifikasi. Harga yang terlalu murah dari channel abal-abal adalah red flag utama.</p><h3>Peran Bengkel Menjaga Reputasi</h3><p>Bengkel yang menjual oli palsu bisa hancur reputasinya oleh satu review viral. Dengan sistem inventory yang mencatat batch dan supplier setiap parts masuk, bengkel bisa melacak asal-usul produk dan menjamin keaslian kepada customer.</p>'],
            ['category' => 'sparepart-oli', 'title' => 'Panduan Memilih Ban Sesuai Gaya Berkendara', 'slug' => 'memilih-ban-sesuai-gaya-berkendara',
                'excerpt' => 'Ban city, HPV, AT, MT — bingung pilih yang mana? Kenali karakter tiap tipe ban agar nyaman, aman, dan hemat BBM.',
                'body' => '<p>Ban adalah satu-satunya komponen yang bersentuhan langsung dengan jalan. Salah pilih tipe ban, nyaman berkendara dan konsumsi BBM ikut kena dampaknya.</p><h3>Ban City / Highway</h3><p>Untuk penggunaan mayoritas perkotaan dan tol: pola tapak rapat, rolling resistance rendah, nyaman dan senyap. Pilihan terbaik untuk MPV dan sedan keluarga.</p><h3>Ban HPV (High Performance Vehicle)</h3><p>Grip tinggi untuk hatchback sporty dan sedan performa. Compound lebih lunak, umur lebih pendek, harga lebih premium.</p><h3>Ban AT (All Terrain)</h3><p>Campuran jalan aspal dan ring track. Pola blok lebih agresif, cocok untuk SUV yang sesekali touring ke luar kota.</p><h3>Ban MT (Mud Terrain)</h3><p>Untuk off-road berat dan medan lumpur. Bising di aspal dan boros BBM — hanya pilih jika benar-benar sering melewati medan ekstrem.</p><h3>Jangan Lupa DOT Code</h3><p>Cek tanggal produksi di samping ban (4 digit: minggu & tahun). Ban lebih dari 5 tahun penyimpanannya mulai mengeras meski belum dipakai. Bengkel profesional selalu mencatat nomor seri ban yang terpasang ke history kendaraan customer.</p>'],

            // ── Berita Otomotif ──
            ['category' => 'berita-otomotif', 'title' => 'Tren EV di Indonesia dan Dampaknya ke Dunia Bengkel', 'slug' => 'tren-ev-dampak-ke-bengkel',
                'excerpt' => 'Mobil listrik makin banyak di jalan raya. Apakah ini ancaman atau peluang emas bagi bengkel konvensional?',
                'body' => '<p>Pertumbuhan kendaraan listrik di Indonesia terus meningkat dari tahun ke tahun. Bagi pemilik bengkel, fenomena ini menimbulkan pertanyaan besar: apakah bengkel konvensional akan ditinggalkan?</p><h3>Yang Berkurang: Servis Mesin Bakar</h3><p>EV tidak punya oli mesin, filter oli, timing belt, atau busi. Layanan tune-up klasik akan menyusut di segmen EV.</p><h3>Yang Bertambah: Layanan Baru</h3><p>Kaki-kaki tetap butuh perawatan: ban, brake pad (regeneratif membuat aus lebih lambat tapi tetap ada), suspension, AC kabin, dan 12V battery. Ditambah layanan khusus: health check baterai, software update, dan inspeksi charging port.</p><h3>Peluang: Sertifikasi Teknisi EV</h3><p>Teknisi yang tersertifikasi high-voltage handling langka dan bernilai tinggi. Bengkel early mover yang melatih teknisinya sekarang akan memonopoli pasar servis EV di wilayahnya.</p><h3>Sistem Bengkel Harus Siap</h3><p>ERP bengkel modern sudah mendukung kategori kendaraan listrik: checklist inspeksi berbeda, skill matrix teknisi EV, dan paket servis khusus. Yang belum bersiap digital justru akan tercepat tersingkir.</p>'],
            ['category' => 'berita-otomotif', 'title' => 'Harga BBM dan Perilaku Servis Konsumen: Analisis Terbaru', 'slug' => 'harga-bbm-perilaku-servis-konsumen',
                'excerpt' => 'Saat harga BBM naik, pola servis customer berubah. Pemahaman pola ini membantu bengkel menyusun promo yang tepat sasaran.',
                'body' => '<p>Data industri menunjukkan pola menarik: setiap kali harga BBM naik, perilaku servis pemilik kendaraan ikut bergeser. Memahami pola ini adalah modal promosi bengkel yang tepat sasaran.</p><h3>Fenomena Menahan Servis</h3><p>Segmen customer sensitif harga cenderung menunda servis rutin. Ini risiko: kerusakan kecil yang tertunda berubah menjadi biaya besar. Edukasi "servis kecil mencegah bengkel besar" sangat efektif pada masa ini.</p><h3>Peluncuran Paket Hemat</h3><p>Paket servis bundling dengan harga flat (misal: ganti oli + tune-up + cek 20 titik) terbukti menahan penurunan kunjungan. Customer merasa kontrol atas pengeluarannya.</p><h3>Voucher dan Loyalty Membuat Retensi Naik</h3><p>Program poin loyalty membuat customer tetap kembali meski sedang hemat. Voucher diskon yang dikirim personal via WhatsApp memiliki conversion rate jauh di atas broadcast media sosial.</p><h3>Gunakan Data, Bukan Feeling</h3><p>Dengan laporan segmentasi customer di ERP bengkel, Anda tahu persis kelompok mana yang kunjungannya turun — lalu sasar mereka dengan promo yang relevan, bukan diskon seragam yang menggerus margin.</p>'],
        ];

        foreach ($posts as $i => $p) {
            BlogPost::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'category_id' => $categories[$p['category']]->id,
                    'author_id' => $admin?->id,
                    'title' => $p['title'],
                    'excerpt' => $p['excerpt'],
                    'content' => $p['body'],
                    'meta_title' => $p['title'].' | Blog Bengkel',
                    'meta_description' => Str::limit($p['excerpt'], 150),
                    'published_at' => now()->subDays(30 - $i * 2)->setTime(9, 0),
                    'is_published' => true,
                ]
            );
        }

        $this->command->info('Blog seeded: '.$categories->count().' categories, '.count($posts).' posts.');
    }
}
