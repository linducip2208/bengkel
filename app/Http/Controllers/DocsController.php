<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DocsController extends Controller
{
    public function index(): View
    {
        return view('docs.index', [
            'sections' => $this->sections(),
            'appName'  => config('app.name', 'Bengkel Paten'),
        ]);
    }

    public function show(string $slug): View
    {
        $sections = $this->sections();

        $section = collect($sections)->firstWhere('slug', $slug);
        abort_unless($section, 404);

        $appName = config('app.name', 'Bengkel Paten');

        $jsonLd = json_encode([
            '@context'   => 'https://schema.org',
            '@type'      => 'TechArticle',
            'headline'   => $section['title'],
            'description' => $section['lead'],
            'inLanguage' => 'id-ID',
            'isPartOf'   => [
                '@type' => 'CreativeWork',
                'name'  => 'Tutorial ' . $appName,
                'url'   => route('docs.index'),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return view('docs.show', [
            'sections' => $sections,
            'section'  => $section,
            'appName'  => $appName,
            'jsonLd'   => $jsonLd,
        ]);
    }

    /**
     * Materi tutorial — diurutkan mengikuti alur bisnis bengkel:
     * dari setup awal → master data → operasional harian → laporan.
     */
    private function sections(): array
    {
        return [
            [
                'slug'  => 'pengantar',
                'icon'  => 'fa-book-open',
                'title' => 'Pengantar Aplikasi',
                'lead'  => 'Sekilas tentang Bengkel Paten dan bagaimana alur kerja harian dipetakan ke menu aplikasi.',
                'body'  => [
                    ['type' => 'p', 'text' => 'Bengkel Paten adalah aplikasi manajemen bengkel berbasis web. Cakupannya menutup seluruh siklus harian: mulai dari kendaraan masuk, jobcard & inspeksi, pemakaian sparepart, sampai invoice, pembayaran, dan laporan keuangan.'],
                    ['type' => 'p', 'text' => 'Tutorial ini menjelaskan urut-urutan pemakaian aplikasi seperti operator bekerja di hari pertama: setup data referensi → daftar pelanggan & kendaraan → proses service → tutup invoice → tinjau laporan.'],
                    ['type' => 'h', 'text' => 'Alur ringkas (end-to-end)'],
                    ['type' => 'ol', 'items' => [
                        'Setup awal: <b>Cabang</b>, jam operasional, hari libur, washbay, currency, dan profil bengkel di <b>Settings</b>.',
                        'Isi <b>Master Data</b> lengkap (jenis kendaraan, merk, BBM, warna, kategori produk, satuan, metode bayar, tarif pajak, kategori repair, tipe & point observasi, kategori checkout).',
                        'Setup <b>Geografi</b> (negara, provinsi, kota) bila perlu alamat terstandar.',
                        'Daftarkan <b>Customer</b> dan <b>Kendaraan</b> milik customer tersebut.',
                        'Buat <b>Service</b> baru → otomatis menghasilkan <b>Jobcard</b>.',
                        'Isi <b>Observation / Checklist</b> kendaraan dan tambahkan parts dari <b>Inventory</b>.',
                        'Tempatkan kendaraan di <b>Washbay</b> yang kosong saat dikerjakan teknisi.',
                        'Lakukan <b>Checkout</b> & cetak <b>Gate Pass</b> saat kendaraan keluar.',
                        'Terbitkan <b>Invoice</b>, terima <b>Payment</b> (bisa cicil), kirim ke customer via <b>WhatsApp</b> atau <b>Email</b>.',
                        'Tinjau <b>Reports</b> harian/bulanan dan <b>Audit & Log</b> untuk monitoring.',
                    ]],
                    ['type' => 'h', 'text' => 'Daftar lengkap modul aplikasi'],
                    ['type' => 'table', 'rows' => [
                        ['Dashboard', 'Ringkasan service hari ini, invoice unpaid, dan stok rendah.'],
                        ['Cabang', 'Multi-branch: daftar cabang, jam operasional, hari libur, washbay.'],
                        ['Master Data', '13 jenis master: vehicle types/brands, fuel, color, product types/units, payment methods, tax rates, repair categories, observation types & points, inspection points, checkout categories.'],
                        ['Customer', 'CRUD pelanggan + import CSV + histori service per customer.'],
                        ['Vehicle', 'CRUD kendaraan + multi-image upload + histori service per kendaraan.'],
                        ['Service', 'Buat service, jobcard otomatis, checklist observasi, upload foto before/after, complete/mark done.'],
                        ['Jobcard', 'Lembar kerja teknisi, print PDF, kirim ke teknisi.'],
                        ['Gate Pass', 'Surat jalan keluar bengkel dengan stempel waktu masuk/keluar.'],
                        ['Washbay', 'Slot fisik di bengkel; status: kosong, dipakai, maintenance.'],
                        ['Inventory', 'Produk, stock record, supplier, purchase (PO), stock opname, stock-adjust.'],
                        ['Sales', 'POS jual parts langsung tanpa service (walk-in).'],
                        ['Invoice', 'Generate invoice dari service/sale, PDF, kirim via WA/Email, partial payment.'],
                        ['Financial', 'Income/Expense non-operasional, history records auto-tracked.'],
                        ['Reports', '4 jenis: service, sales, stock, financial; export PDF & Excel.'],
                        ['Notification & Reminder', 'Template WA/Email, reminder ganti oli otomatis, kirim batch.'],
                        ['Geografi & Currency', 'Negara, provinsi, kota, multi-currency dengan default.'],
                        ['Audit & Log', 'Stock history (audit trail stok), log notifikasi, catatan internal.'],
                        ['Settings', 'Profil bengkel, notification templates, reminders, custom fields.'],
                    ]],
                    ['type' => 'note', 'text' => 'Setiap menu yang disebut di tutorial dapat diklik langsung dari sidebar aplikasi setelah login.'],
                    ['type' => 'screenshot', 'file' => 'dashboard.png', 'label' => 'Dashboard Utama', 'path' => '/', 'caption' => 'Tampilan dashboard setelah login — ringkasan service, revenue, stok rendah, dan recent jobs.'],
                ],
            ],
            [
                'slug'  => 'persiapan-awal',
                'icon'  => 'fa-rocket',
                'title' => '1. Persiapan Awal',
                'lead'  => 'Login, kenalan dengan dashboard, lalu setup profil bengkel & jam operasional sebelum dipakai operator.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Login'],
                    ['type' => 'ol', 'items' => [
                        'Buka URL aplikasi dan login dengan akun admin yang diberikan.',
                        'Default credentials: <code>admin@bengkelpaten.id / password</code> — <b>wajib ganti</b> sebelum production.',
                        'Setelah login, sistem akan membuka <b>Dashboard</b>.',
                    ]],
                    ['type' => 'h', 'text' => 'Dashboard — apa yang bisa dilihat'],
                    ['type' => 'p', 'text' => 'Dashboard menampilkan ringkasan 1 layar untuk keputusan harian:'],
                    ['type' => 'ul', 'items' => [
                        '<b>Service hari ini</b> — jumlah job aktif (Open + In Process)',
                        '<b>Revenue hari ini & bulan ini</b> — total invoice paid',
                        '<b>Outstanding invoices</b> — jumlah invoice belum lunas',
                        '<b>Stok rendah</b> — produk yang mendekati minimum stock',
                        '<b>Recent services</b> — 10 service terakhir untuk akses cepat',
                        '<b>Upcoming services</b> — kendaraan yang due servis bulan ini (berdasarkan reminder)',
                    ]],
                    ['type' => 'h', 'text' => 'Topbar — kontrol cepat'],
                    ['type' => 'ul', 'items' => [
                        '<b>Cabang switcher</b> — pindah konteks antar cabang (kalau multi-branch)',
                        '<b>User menu</b> di kanan atas — profile, settings, logout',
                    ]],
                    ['type' => 'h', 'text' => 'Setting profil bengkel'],
                    ['type' => 'ol', 'items' => [
                        'Klik menu <b>Settings → General Settings</b>.',
                        'Isi nama bengkel, alamat, no HP, email, NPWP, dan upload logo.',
                        'Set mata uang default di <b>Geografi & Currency → Currencies</b> (IDR sudah ter-seed).',
                        'Klik <b>Simpan</b>. Logo dan nama otomatis muncul di header & invoice.',
                    ]],
                    ['type' => 'tip', 'text' => 'Logo idealnya format PNG transparan ukuran ≤ 200KB agar invoice PDF tetap ringan.'],
                    ['type' => 'screenshot', 'file' => 'settings.png', 'label' => 'Settings', 'path' => '/settings', 'caption' => 'Halaman General Settings — isi profil bengkel, logo, alamat, dan kontak.'],
                ],
            ],
            [
                'slug'  => 'cabang',
                'icon'  => 'fa-building',
                'title' => '2. Cabang & Jam Operasional',
                'lead'  => 'Multi-cabang: daftar cabang, jam buka, hari libur, dan washbay slot service.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Daftarkan cabang'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Cabang → Daftar Cabang → + Tambah Cabang</b>.',
                        'Isi nama, kode (mis. PST untuk Pusat), alamat, telepon, email.',
                        'Centang <b>Cabang Aktif</b>, klik <b>Simpan</b>.',
                        'Cabang muncul di dropdown switcher topbar kanan-atas.',
                    ]],
                    ['type' => 'h', 'text' => 'Set jam operasional per cabang'],
                    ['type' => 'ol', 'items' => [
                        'Klik nama cabang → detail cabang.',
                        'Di kartu <b>Jam Operasional</b>, klik <b>Set Jam</b>.',
                        'Pilih hari, isi jam buka & tutup. Centang <i>Hari ini Tutup</i> kalau libur reguler (mis. Minggu).',
                        'Ulang untuk setiap hari Senin-Sabtu.',
                    ]],
                    ['type' => 'h', 'text' => 'Hari libur'],
                    ['type' => 'p', 'text' => 'Tambah tanggal merah, libur nasional, atau cuti bersama. Centang <i>Berulang tiap tahun</i> untuk tanggal yang sama tiap tahun (mis. 17 Agustus). Bisa per-cabang atau global semua cabang.'],
                    ['type' => 'h', 'text' => 'Switching cabang aktif'],
                    ['type' => 'p', 'text' => 'Di topbar ada dropdown <b>Cabang</b>. Pilih cabang spesifik → seluruh data operasional (customer, service, invoice, dst) otomatis ter-filter ke cabang itu. Pilih <i>Semua Cabang</i> untuk lihat data global.'],
                    ['type' => 'note', 'text' => 'Cabang yang masih punya data operasional (customer/service/invoice) tidak bisa dihapus — sistem menolak demi audit trail.'],
                ],
            ],
            [
                'slug'  => 'master-data',
                'icon'  => 'fa-database',
                'title' => '3. Master Data',
                'lead'  => 'Data referensi yang dipakai berulang oleh modul lain. Isi sekali, dipakai berulang.',
                'body'  => [
                    ['type' => 'p', 'text' => 'Master data wajib diisi <b>sebelum</b> Anda mulai mencatat customer atau kendaraan. Jika dilewati, beberapa form akan kosong dropdown-nya.'],
                    ['type' => 'h', 'text' => 'Daftar master data & isinya'],
                    ['type' => 'table', 'rows' => [
                        ['Vehicle Types', 'Jenis kendaraan: Mobil, Motor, Truk, dst.'],
                        ['Vehicle Brands', 'Merk: Toyota, Honda, Yamaha, Suzuki, …'],
                        ['Fuel Types', 'Pertalite, Pertamax, Solar, Listrik, …'],
                        ['Colors', 'Warna kendaraan untuk dropdown registrasi.'],
                        ['Product Types', 'Kategori sparepart: oli, ban, filter, aki, …'],
                        ['Product Units', 'Satuan: pcs, liter, set, meter, …'],
                        ['Payment Methods', 'Cash, Transfer Bank, QRIS, Debit, EDC, …'],
                        ['Tax Rates', 'PPN 11%, PPh, atau pajak khusus.'],
                        ['Repair Categories', 'Tune up, ganti oli, overhaul, body repair, dst.'],
                        ['Observation Types', 'Kelompok inspeksi: Eksterior, Interior, Mesin, Kaki-Kaki, Kelistrikan, Test Drive.'],
                        ['Observation Points', 'Detail point per kelompok (mis. Mesin → Oli/Filter/Busi). Dipakai di checklist service.'],
                        ['Inspection Points', 'Library inspection point reusable lintas service (template global).'],
                        ['Checkout Categories', 'Kategori final checkout: BBM, Kondisi Mesin, Body & Kabin, dst.'],
                    ]],
                    ['type' => 'h', 'text' => 'Cara menambah master data'],
                    ['type' => 'ol', 'items' => [
                        'Buka menu <b>Master Data → [nama list]</b>.',
                        'Klik tombol <b>+ Tambah</b> di pojok kanan atas.',
                        'Isi nama, lalu klik <b>Simpan</b>.',
                        'Untuk mengubah, klik ikon pensil. Untuk menghapus, klik ikon tong sampah.',
                    ]],
                    ['type' => 'note', 'text' => 'Master data yang sudah pernah dipakai (misal "Toyota" sudah punya 5 kendaraan) tidak bisa dihapus — sistem akan menolak demi menjaga integritas histori.'],
                ],
            ],
            [
                'slug'  => 'customer',
                'icon'  => 'fa-users',
                'title' => '4. Manajemen Customer',
                'lead'  => 'Cara mendaftarkan pelanggan baru, melihat histori service, dan impor massal dari Excel.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Tambah customer baru'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Customer → Add Customer</b>.',
                        'Isi nama, no HP, email (opsional), alamat.',
                        'Untuk pelanggan perusahaan, isi nama perusahaan & NPWP agar muncul di invoice.',
                        'Klik <b>Simpan</b>. Customer baru muncul di list.',
                    ]],
                    ['type' => 'h', 'text' => 'Lihat histori customer'],
                    ['type' => 'p', 'text' => 'Klik nama customer di list. Halaman detail menampilkan semua kendaraan, service yang pernah dikerjakan, invoice & status pembayaran, dan total spending.'],
                    ['type' => 'h', 'text' => 'Impor massal'],
                    ['type' => 'ol', 'items' => [
                        'Klik <b>Customer → Import</b>.',
                        'Download template CSV yang disediakan.',
                        'Isi sesuai kolom (nama, hp, email, alamat).',
                        'Upload file. Sistem akan menolak baris yang nomor HP-nya duplikat.',
                    ]],
                    ['type' => 'tip', 'text' => 'Nomor HP dijadikan identitas unik untuk kirim reminder otomatis. Pastikan format diawali +62 atau 08.'],
                    ['type' => 'screenshot', 'file' => 'customer-list.png', 'label' => 'Daftar Customer', 'path' => '/customers', 'caption' => 'List customer dengan histori service dan total spending.'],
                ],
            ],
            [
                'slug'  => 'kendaraan',
                'icon'  => 'fa-car',
                'title' => '5. Manajemen Kendaraan',
                'lead'  => 'Setiap kendaraan terikat ke satu customer dan menjadi objek utama service.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Daftarkan kendaraan'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Vehicle → Add Vehicle</b>.',
                        'Pilih customer pemilik (atau ketik untuk autocomplete).',
                        'Isi nomor plat, jenis, merk, model, tahun, warna, BBM, no rangka, no mesin, odometer saat ini.',
                        'Upload foto kendaraan (boleh multi foto: depan, belakang, sisi, kerusakan).',
                        'Klik <b>Simpan</b>.',
                    ]],
                    ['type' => 'note', 'text' => 'Nomor plat <b>unik</b> di seluruh sistem. Jika plat sama sudah terdaftar, sistem menolak dengan pesan jelas.'],
                    ['type' => 'h', 'text' => 'Foto kondisi awal'],
                    ['type' => 'p', 'text' => 'Selalu ambil foto sebelum kendaraan masuk bengkel. Foto ini menjadi bukti kondisi awal saat ada komplain pelanggan setelah service.'],
                ],
            ],
            [
                'slug'  => 'service-workflow',
                'icon'  => 'fa-tools',
                'title' => '6. Alur Service (Jantung Aplikasi)',
                'lead'  => 'Workflow lengkap dari kendaraan masuk sampai siap diserahkan ke customer.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Status service'],
                    ['type' => 'ul', 'items' => [
                        '<b>Open</b> — service baru dibuat, belum dikerjakan teknisi.',
                        '<b>In Process</b> — teknisi sudah mulai mengerjakan.',
                        '<b>Done</b> — pekerjaan selesai, kendaraan siap diambil.',
                    ]],
                    ['type' => 'h', 'text' => 'Langkah membuat service'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Service → Add Service</b>.',
                        'Pilih customer (search by nama / HP).',
                        'Pilih kendaraan customer tersebut (dropdown otomatis ter-filter).',
                        'Pilih <b>Repair Category</b> (Tune Up, Ganti Oli, dst).',
                        'Isi tanggal masuk, odometer masuk, keluhan customer.',
                        'Klik <b>Simpan</b>. Sistem otomatis generate nomor service & nomor jobcard.',
                    ]],
                    ['type' => 'h', 'text' => 'Setelah service dibuat'],
                    ['type' => 'ol', 'items' => [
                        'Buka detail service → klik tab <b>Observation Checklist</b>.',
                        'Centang point inspeksi yang ditemukan (rem aus, ban gundul, dst).',
                        'Tambahkan parts/sparepart dari inventory → stok otomatis berkurang.',
                        'Tambahkan jasa & biaya, tax otomatis dihitung.',
                        'Upload foto before/after.',
                        'Saat selesai, klik tombol <b>Mark as Complete</b> → status berubah ke <i>Done</i>.',
                    ]],
                    ['type' => 'tip', 'text' => 'Foto before/after meningkatkan kepercayaan customer dan menjadi bahan marketing di social media.'],
                    ['type' => 'screenshot', 'file' => 'service-list.png', 'label' => 'Daftar Service', 'path' => '/services', 'caption' => 'Status service: Open, In Process, Done — visual sekilas untuk front-desk.'],
                ],
            ],
            [
                'slug'  => 'jobcard',
                'icon'  => 'fa-clipboard-list',
                'title' => '7. Jobcard & Checkout',
                'lead'  => 'Jobcard adalah lembar kerja teknisi. Setiap service punya satu jobcard.',
                'body'  => [
                    ['type' => 'p', 'text' => 'Jobcard otomatis terbuat ketika Anda membuat <b>Service</b> baru. Anda tinggal melengkapi field-nya:'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Service → Jobcards</b>, klik nomor jobcard.',
                        'Isi tanggal masuk, jam masuk, odometer, dan rekomendasi service berikutnya (odometer atau tanggal).',
                        'Tugaskan teknisi yang menangani.',
                        'Klik <b>Print Jobcard</b> untuk lembar fisik yang ditandatangani customer & teknisi.',
                    ]],
                    ['type' => 'screenshot', 'file' => 'jobcard-list.png', 'label' => 'Daftar Jobcard', 'path' => '/jobcards', 'caption' => 'Jobcard — lembar kerja teknisi per service. Bisa di-print untuk tanda tangan.'],
                    ['type' => 'h', 'text' => 'Checkout kendaraan'],
                    ['type' => 'p', 'text' => 'Sebelum kendaraan keluar, lakukan checkout untuk mencatat kondisi akhir:'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Checkouts → [nomor service]</b>.',
                        'Centang kategori checkout (BBM, ban, AC, audio, dst — sesuai master data).',
                        'Tulis catatan akhir, lalu <b>Simpan</b>.',
                    ]],
                ],
            ],
            [
                'slug'  => 'geografi-currency',
                'icon'  => 'fa-globe',
                'title' => '8. Geografi & Currency',
                'lead'  => 'Master data wilayah dan mata uang. Optional kalau alamat customer cukup free-text.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Currency multi-mata uang'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Geografi & Currency → Currencies → + Tambah</b>.',
                        'Isi code 3-huruf (IDR, USD, dst), nama, simbol (Rp, $), exchange rate.',
                        'Centang <b>Jadikan Default</b> untuk currency utama. Semua harga di app akan otomatis pakai simbol & format-nya.',
                    ]],
                    ['type' => 'tip', 'text' => 'Default IDR sudah di-seed. Hanya perlu ubah kalau bengkel kerja dengan customer luar negeri.'],
                    ['type' => 'h', 'text' => 'Negara / Provinsi / Kota (opsional)'],
                    ['type' => 'p', 'text' => 'Bila ingin alamat customer terstandar (mis. untuk laporan agregat per kota), isi hierarki <b>Country → State → City</b> di menu Geografi.'],
                ],
            ],
            [
                'slug'  => 'gate-pass',
                'icon'  => 'fa-ticket-alt',
                'title' => '9. Gate Pass',
                'lead'  => 'Surat jalan keluar kendaraan dari bengkel — bukti resmi serah terima.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Gate Passes → Add Gate Pass</b>, atau dari halaman service klik <b>Generate Gate Pass</b>.',
                        'Sistem otomatis mengisi data kendaraan & customer.',
                        'Isi jam keluar dan nama yang menjemput (jika bukan owner).',
                        'Klik <b>Print</b>. Lembar gate pass diberi tanda tangan satpam.',
                        'Saat kendaraan benar-benar keluar, klik <b>Mark Exit</b> agar stempel waktu tercatat.',
                    ]],
                    ['type' => 'note', 'text' => 'Gate pass tidak bisa dibuat jika invoice masih <b>Unpaid</b> — kecuali admin memberi persetujuan khusus.'],
                    ['type' => 'screenshot', 'file' => 'gate-pass-list.png', 'label' => 'Daftar Gate Pass', 'path' => '/gate-passes', 'caption' => 'Gate Pass — surat jalan keluar kendaraan. Stempel waktu masuk/keluar tercatat.'],
                ],
            ],
            [
                'slug'  => 'washbay',
                'icon'  => 'fa-shower',
                'title' => '10. Washbay (Slot Service)',
                'lead'  => 'Manajemen slot fisik bengkel — kanban ringan untuk lihat slot mana yang kosong / dipakai.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Setup washbay'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Cabang → Washbay / Slot → + Tambah Washbay</b>.',
                        'Isi nama (Bay 1, Bay 2, Slot A), pilih cabang, set status awal <i>Kosong</i>.',
                        'Ulangi untuk setiap slot fisik di bengkel.',
                    ]],
                    ['type' => 'h', 'text' => 'Pakai washbay saat service'],
                    ['type' => 'ol', 'items' => [
                        'Saat service mulai dikerjakan teknisi, buka detail washbay → edit → status <i>Dipakai</i> + pilih service yang aktif.',
                        'Status berubah jadi kuning di kanban.',
                        'Setelah service selesai, klik <b>Kosongkan</b> di kartu washbay → status balik ke <i>Kosong</i> (hijau).',
                    ]],
                    ['type' => 'tip', 'text' => 'Status <i>Maintenance</i> (merah) untuk slot yang sedang renovasi/rusak — biar tidak dipakai sementara.'],
                ],
            ],
            [
                'slug'  => 'inventory',
                'icon'  => 'fa-boxes',
                'title' => '11. Inventory (Sparepart & Supplier)',
                'lead'  => 'Kelola stok parts, supplier, pembelian, dan stock opname.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Tambah produk/sparepart'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Inventory → Products</b>, klik <b>+ Tambah</b>.',
                        'Isi kode produk, nama, kategori (product type), satuan (product unit).',
                        'Isi harga beli, harga jual, dan stok awal.',
                        'Pilih supplier utama.',
                        'Upload foto produk (opsional).',
                    ]],
                    ['type' => 'h', 'text' => 'Pembelian ke supplier'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Inventory → Purchases → Add Purchase</b>.',
                        'Pilih supplier, tanggal PO, dan tambahkan produk + qty + harga.',
                        'Klik <b>Simpan</b>. Status PO = <i>Pending</i>.',
                        'Saat barang datang, klik <b>Mark Received</b> → stok otomatis bertambah.',
                    ]],
                    ['type' => 'h', 'text' => 'Stock opname'],
                    ['type' => 'p', 'text' => 'Buka <b>Inventory → Stock</b>. Stock opname memungkinkan koreksi stok manual setelah hitung fisik. Selisih akan tercatat di histori untuk audit.'],
                    ['type' => 'tip', 'text' => 'Lakukan stock opname minimal sekali sebulan agar laporan akurat.'],
                    ['type' => 'screenshot', 'file' => 'product-list.png', 'label' => 'Daftar Produk', 'path' => '/products', 'caption' => 'Inventory produk/sparepart — kelola stok, harga, supplier, dan gambar produk.'],
                ],
            ],
            [
                'slug'  => 'sales',
                'icon'  => 'fa-shopping-cart',
                'title' => '12. Penjualan (Sales)',
                'lead'  => 'Untuk penjualan parts langsung tanpa service, atau penjualan kendaraan bekas.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Sales → Add Sale</b>.',
                        'Pilih customer (atau buat baru jika walk-in).',
                        'Tambahkan produk + qty. Harga ambil dari master, tetapi bisa diskon manual.',
                        'Pilih metode bayar.',
                        'Klik <b>Simpan</b> → invoice otomatis terbuat, stok otomatis berkurang.',
                    ]],
                    ['type' => 'note', 'text' => 'Penjualan langsung tetap mempengaruhi laporan keuangan & stok, jadi pastikan setiap transaksi over-the-counter dicatat di sini, bukan di luar sistem.'],
                ],
            ],
            [
                'slug'  => 'invoice-payment',
                'icon'  => 'fa-file-invoice',
                'title' => '13. Invoice & Pembayaran',
                'lead'  => 'Penerbitan invoice, pembayaran cicil/lunas, kirim invoice via WhatsApp/email.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Buat invoice dari service'],
                    ['type' => 'ol', 'items' => [
                        'Setelah service selesai, buka detail service → klik <b>Generate Invoice</b>.',
                        'Cek line item, jumlah, dan pajak. Tambah diskon jika perlu.',
                        'Klik <b>Simpan</b>. Invoice otomatis berstatus <i>Unpaid</i>.',
                    ]],
                    ['type' => 'h', 'text' => 'Terima pembayaran'],
                    ['type' => 'ol', 'items' => [
                        'Buka invoice → klik <b>Tambah Payment</b>.',
                        'Isi jumlah bayar (boleh sebagian), pilih metode bayar.',
                        'Klik <b>Simpan</b>. Status berubah otomatis: <i>Half Paid</i> atau <i>Full Paid</i>.',
                        'Histori pembayaran tampil di tab <b>Payment History</b>.',
                    ]],
                    ['type' => 'h', 'text' => 'Kirim invoice'],
                    ['type' => 'ul', 'items' => [
                        '<b>PDF</b> — klik <i>Download PDF</i> dari detail invoice.',
                        '<b>WhatsApp</b> — klik <i>Send via WA</i>, sistem buka WA Web dengan pesan & link terisi.',
                        '<b>Email</b> — klik <i>Send via Email</i>, butuh SMTP yang dikonfigurasi di Settings.',
                    ]],
                    ['type' => 'screenshot', 'file' => 'invoice-list.png', 'label' => 'Daftar Invoice', 'path' => '/invoices', 'caption' => 'Invoice management — status Unpaid/Half Paid/Full Paid, kirim via WA/Email, download PDF.'],
                ],
            ],
            [
                'slug'  => 'keuangan',
                'icon'  => 'fa-chart-line',
                'title' => '14. Keuangan (Income & Expense)',
                'lead'  => 'Catat pemasukan & pengeluaran non-operasional bengkel.',
                'body'  => [
                    ['type' => 'p', 'text' => 'Modul ini untuk transaksi keuangan <b>di luar</b> service & sales. Contoh: pemasukan dari sewa lift, pengeluaran listrik, gaji, sewa kios, dst.'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Financial → Income</b> atau <b>Expense</b>.',
                        'Klik <b>+ Tambah</b>, pilih kategori, isi jumlah & tanggal.',
                        'Lampirkan kuitansi (opsional, foto/PDF).',
                    ]],
                    ['type' => 'tip', 'text' => 'Disiplinkan operator mencatat setiap pengeluaran bahkan yang kecil — ini bahan utama laporan profit harian.'],
                ],
            ],
            [
                'slug'  => 'laporan',
                'icon'  => 'fa-chart-bar',
                'title' => '15. Laporan',
                'lead'  => 'Ringkasan performa bengkel: service, sales, stok, dan keuangan.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Jenis laporan'],
                    ['type' => 'table', 'rows' => [
                        ['Service Report', 'Volume service per kategori & per teknisi.'],
                        ['Sales Report', 'Penjualan parts/kendaraan per periode.'],
                        ['Stock Report', 'Stok berjalan, slow-moving items, reorder list.'],
                        ['Financial Report', 'Profit/loss = (income+sales+service) − expense.'],
                    ]],
                    ['type' => 'h', 'text' => 'Cara pakai'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Reports → [jenis]</b>.',
                        'Pilih rentang tanggal di filter atas.',
                        'Klik <b>Export PDF</b> atau <b>Export Excel</b> untuk arsip.',
                    ]],
                    ['type' => 'screenshot', 'file' => 'report-service.png', 'label' => 'Laporan Service', 'path' => '/reports/service', 'caption' => 'Service Report — filter periode, export PDF/Excel, chart interaktif.'],
                ],
            ],
            [
                'slug'  => 'notifikasi-reminder',
                'icon'  => 'fa-bell',
                'title' => '16. Notifikasi & Reminder',
                'lead'  => 'Kirim pengingat service berkala otomatis ke customer.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Template notifikasi'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Settings → Notification Templates</b>.',
                        'Edit template untuk: konfirmasi service, invoice, reminder oli, ucapan ulang tahun.',
                        'Gunakan variabel seperti <code>{customer_name}</code>, <code>{plate}</code>, <code>{next_service_date}</code> — sistem otomatis isi.',
                    ]],
                    ['type' => 'h', 'text' => 'Reminder otomatis'],
                    ['type' => 'p', 'text' => 'Buka <b>Settings → Reminders</b>. Sistem otomatis menemukan kendaraan yang sudah jatuh tempo service (berdasarkan odometer atau tanggal). Klik <b>Send Scheduled</b> agar pesan terkirim batch ke WA/email mereka.'],
                    ['type' => 'tip', 'text' => 'Reminder oli 3-bulanan adalah cara termurah menggandakan retention pelanggan.'],
                ],
            ],
            [
                'slug'  => 'pos-kasir',
                'icon'  => 'fa-cash-register',
                'title' => '12B. POS Kasir (Retail Module)',
                'lead'  => 'Terminal kasir cart-style untuk jual sparepart langsung — terintegrasi penuh dengan inventory, invoice, dan stock history.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Konsep POS Kasir'],
                    ['type' => 'p', 'text' => 'Berbeda dengan modul <b>Sales (Kendaraan)</b> yang untuk jual kendaraan bekas, <b>POS Kasir</b> adalah counter retail untuk jual sparepart eceran — workflow cart-style dengan barcode/search produk, kalkulasi otomatis, dan struk thermal.'],
                    ['type' => 'h', 'text' => 'Workflow lengkap'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>POS Kasir → Terminal Kasir</b> → buka sesi dengan saldo awal di laci.',
                        'Search/scan produk → klik untuk add ke keranjang.',
                        'Pilih customer (boleh Walk-in), pilih payment method, masukkan uang bayar.',
                        'Klik <b>Bayar & Cetak Struk</b> → invoice + stok berkurang + struk thermal auto-print.',
                        'Akhir shift: <b>Tutup Sesi</b> dengan input saldo akhir di laci → sistem hitung selisih kas.',
                    ]],
                    ['type' => 'tip', 'text' => 'Tekan <code>Enter</code> di kolom search saat hanya 1 hasil → otomatis tambah ke cart. Sangat cepat untuk kasir berpengalaman.'],
                    ['type' => 'h', 'text' => 'Otomatis terjadi setelah transaksi'],
                    ['type' => 'ul', 'items' => [
                        '<b>Stok berkurang</b> + audit trail di Stock History',
                        '<b>Invoice ter-generate</b> dengan prefix <code>POS-YYYYMMDD-XXXX</code>',
                        '<b>Payment record</b> langsung lunas (no cicilan)',
                        '<b>Struk thermal</b> auto-print',
                        '<b>Branch_id</b> ikut sesi POS (multi-cabang aware)',
                    ]],
                    ['type' => 'note', 'text' => 'Hanya boleh ada <b>1 sesi POS terbuka per user</b>. Wajib tutup sesi sebelum buka sesi baru — supaya audit kas akurat.'],
                ],
            ],
            [
                'slug'  => 'hrm-komisi',
                'icon'  => 'fa-user-tie',
                'title' => '12C. HRM — Komisi Teknisi',
                'lead'  => 'Hitung & bayar komisi teknisi per service. % komisi diset saat assign teknisi.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Pola komisi standar'],
                    ['type' => 'table', 'rows' => [
                        ['% dari jasa', 'Default 10-15%. Mis. jasa Rp 200rb × 15% = Rp 30rb.'],
                        ['Lead vs Assistant', 'Lead 70%, assistant 30% kalau 2 teknisi 1 job.'],
                        ['Override manual', 'Edit komisi langsung di list kalau perlu adjust.'],
                    ]],
                    ['type' => 'h', 'text' => 'Workflow bayar komisi'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>HRM Teknisi → Komisi Teknisi</b>.',
                        'Filter status = Belum Dibayar, lihat total outstanding.',
                        'Centang baris yang mau dibayar → klik <b>Tandai Dibayar (Batch)</b>.',
                        'Atau per-baris: klik tombol centang hijau.',
                        'Sistem catat paid_at, paid_by (admin yang approve).',
                    ]],
                    ['type' => 'h', 'text' => 'Laporan komisi'],
                    ['type' => 'p', 'text' => 'Buka <b>HRM Teknisi → Laporan Komisi</b>. Filter periode → per teknisi tampil jumlah service, total komisi, sudah dibayar vs belum.'],
                ],
            ],
            [
                'slug'  => 'audit-log',
                'icon'  => 'fa-history',
                'title' => '17. Audit Trail & Log',
                'lead'  => 'Lacak perubahan stok, log notifikasi terkirim, dan catatan internal.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Stock History'],
                    ['type' => 'p', 'text' => 'Buka <b>Audit & Log → Stock History</b>. Setiap pergerakan stok (pembelian, penjualan, pemakaian service, opname, adjustment) otomatis tercatat dengan: stok sebelum, perubahan, stok sesudah, user, dan referensi dokumen.'],
                    ['type' => 'tip', 'text' => 'Filter by produk + tanggal untuk audit stok bulanan. Bisa juga sebagai bukti kalau ada selisih stok fisik vs sistem.'],
                    ['type' => 'h', 'text' => 'Log Notifikasi (Email & WA)'],
                    ['type' => 'p', 'text' => 'Setiap kali sistem kirim invoice via email atau reminder via WA, otomatis tercatat di <b>Audit & Log → Log Notifikasi</b>. Tampil status <i>Sent</i> atau <i>Failed</i> + pesan error kalau gagal.'],
                    ['type' => 'h', 'text' => 'Catatan Internal'],
                    ['type' => 'p', 'text' => 'Buka <b>Audit & Log → Catatan Internal</b>. Catatan internal yang menempel ke entity tertentu (customer, kendaraan, service, invoice) untuk komunikasi antar-staff (mis. "Customer minta diskon", "Sparepart impor delay").'],
                ],
            ],
            [
                'slug'  => 'custom-fields',
                'icon'  => 'fa-puzzle-piece',
                'title' => '18. Custom Fields',
                'lead'  => 'Field tambahan custom per modul tanpa coding — fleksibel sesuai kebutuhan bengkel.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Bikin custom field'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Settings → Custom Fields → + Tambah Field</b>.',
                        'Pilih modul (customer/vehicle/service/invoice/product/supplier).',
                        'Isi nama field (mis. "Tahun Beli Mobil"), pilih tipe (text/number/date/select/boolean/textarea).',
                        'Untuk type=select, isi opsi (satu per baris).',
                        'Centang <i>Wajib diisi</i> kalau mandatory.',
                        'Klik Simpan. Field akan muncul otomatis di form modul terkait.',
                    ]],
                    ['type' => 'tip', 'text' => 'Contoh pakai: tambah field "Sumber Pelanggan" di customer (Walk-in / Referral / Online) untuk analisa marketing.'],
                ],
            ],
            [
                'slug'  => 'license-pairing',
                'icon'  => 'fa-key',
                'title' => '19. License Pairing (Aktivasi Aplikasi)',
                'lead'  => 'Aplikasi ini dilindungi license whitelabel.co.id — pairing sekali per domain.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Kapan wizard pairing muncul?'],
                    ['type' => 'p', 'text' => 'Saat install pertama di production (bukan localhost), buka domain → otomatis redirect ke <code>/__pair</code> wizard. Tanpa pairing, semua route ke-block.'],
                    ['type' => 'h', 'text' => 'Cara pairing'],
                    ['type' => 'ol', 'items' => [
                        'Beli license di <b>https://whitelabel.co.id</b>.',
                        'Buka menu <b>My Licenses</b> di marketplace → copy <i>Activation Key</i>.',
                        'Buka domain aplikasi → wizard <code>/__pair</code> muncul.',
                        'Paste activation key → submit → sistem validasi ke marketplace → sukses.',
                        'Setelah itu file <code>storage/app/.license.lock</code> dibuat — pairing tersimpan untuk domain ini.',
                    ]],
                    ['type' => 'note', 'text' => 'Regular license = 1 domain. Extended = 3 domain. Pindah domain (revoke + re-pair) bisa via marketplace admin panel.'],
                    ['type' => 'tip', 'text' => 'Di local environment (localhost / *.test), license bypass otomatis aktif — tidak perlu pairing untuk development.'],
                ],
            ],
            [
                'slug'  => 'faq',
                'icon'  => 'fa-question-circle',
                'title' => 'FAQ & Troubleshooting',
                'lead'  => 'Pertanyaan yang paling sering diajukan operator bengkel.',
                'body'  => [
                    ['type' => 'faq', 'items' => [
                        ['q' => 'Saya tidak bisa hapus master data, kenapa?',
                         'a' => 'Data master yang sudah terpakai (mis. brand "Toyota" sudah ada kendaraannya) sengaja dikunci agar histori tetap valid. Hapus referensinya dulu sebelum menghapus master.'],
                        ['q' => 'Stok minus, bagaimana?',
                         'a' => 'Berarti ada pemakaian yang tidak diimbangi pembelian. Lakukan <b>Stock Opname</b> untuk koreksi, lalu cek di histori produk transaksi mana yang menyebabkan.'],
                        ['q' => 'Invoice salah, apakah bisa dihapus?',
                         'a' => 'Invoice yang sudah ada pembayaran tidak bisa dihapus. Buat invoice koreksi (credit note) atau hapus pembayaran dulu jika memang salah input.'],
                        ['q' => 'Customer minta refund, bagaimana?',
                         'a' => 'Catat refund sebagai <b>Expense</b> dengan kategori "Refund Customer" dan lampirkan no invoice di catatan.'],
                        ['q' => 'Akun saya tiba-tiba tidak bisa login?',
                         'a' => 'Hubungi admin utama untuk reset password. Jika seluruh login bermasalah, kemungkinan license expired — cek halaman pairing license.'],
                        ['q' => 'Apakah bisa multi-cabang?',
                         'a' => 'Sudah bisa. Tambah cabang di menu <b>Cabang → Daftar Cabang</b>. Lalu pakai dropdown <b>switcher cabang</b> di topbar untuk pindah konteks — semua data operasional otomatis ter-filter per cabang.'],
                        ['q' => 'Kenapa angka muncul jadi raw JSON di beberapa halaman?',
                         'a' => 'Sudah diperbaiki. Kalau masih terjadi, jalankan <code>php artisan view:clear && php artisan route:clear</code>.'],
                        ['q' => 'Cara hubungi support cepat?',
                         'a' => 'Hubungi <b>WhatsApp 081296052010</b> — tim support standby Senin-Sabtu jam kerja.'],
                         ['q' => 'Backup data otomatis?',
                          'a' => 'Backup database harian dilakukan via cron server. Anda juga bisa ekspor manual lewat <b>Reports → Export Excel</b> per modul.'],
                    ]],
                ],
            ],
            [
                'slug'  => 'galeri-visual',
                'icon'  => 'fa-images',
                'title' => '20. Galeri Visual — Semua Halaman',
                'lead'  => 'Lihat tampilan semua halaman aplikasi — dari dashboard, customer, service, inventory, sampai laporan.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Dashboard & Navigasi Utama'],
                    ['type' => 'screenshot', 'file' => 'dashboard.png', 'label' => 'Dashboard Utama', 'path' => '/', 'caption' => 'Dashboard — ringkasan harian: service aktif, revenue, invoice unpaid, stok rendah.'],
                    ['type' => 'screenshot', 'file' => 'branch-list.png', 'label' => 'Daftar Cabang', 'path' => '/branches', 'caption' => 'Multi-cabang management — daftar, jam operasional, hari libur.'],

                    ['type' => 'h', 'text' => 'Customer & Kendaraan'],
                    ['type' => 'screenshot', 'file' => 'customer-list.png', 'label' => 'Daftar Customer', 'path' => '/customers', 'caption' => 'Customer dengan total service, spending, dan loyalty points.'],
                    ['type' => 'screenshot', 'file' => 'customer-create.png', 'label' => 'Tambah Customer', 'path' => '/customers/create', 'caption' => 'Form tambah customer — nama, HP, email, alamat, NPWP.'],
                    ['type' => 'screenshot', 'file' => 'vehicle-list.png', 'label' => 'Daftar Kendaraan', 'path' => '/vehicles', 'caption' => 'Semua kendaraan terdaftar — nomor plat unik, detail, foto, histori service.'],

                    ['type' => 'h', 'text' => 'Service & Jobcard'],
                    ['type' => 'screenshot', 'file' => 'service-list.png', 'label' => 'Daftar Service', 'path' => '/services', 'caption' => 'Status service Open/In Process/Done + customer & kendaraan.'],
                    ['type' => 'screenshot', 'file' => 'service-create.png', 'label' => 'Buat Service Baru', 'path' => '/services/create', 'caption' => 'Form service — pilih customer & kendaraan, repair category, tanggal, keluhan.'],
                    ['type' => 'screenshot', 'file' => 'jobcard-list.png', 'label' => 'Daftar Jobcard', 'path' => '/jobcards', 'caption' => 'Lembar kerja teknisi — print, assign teknisi, checklist observasi.'],

                    ['type' => 'h', 'text' => 'Inventory & Supplier'],
                    ['type' => 'screenshot', 'file' => 'product-list.png', 'label' => 'Daftar Produk', 'path' => '/products', 'caption' => 'Inventory sparepart — stok, harga jual/beli, supplier, gambar.'],
                    ['type' => 'screenshot', 'file' => 'product-stock-opname.png', 'label' => 'Stock Opname', 'path' => '/products/stock-opname', 'caption' => 'Stock opname — hitung fisik vs sistem, selisih tertandai, auto-adjust.'],
                    ['type' => 'screenshot', 'file' => 'purchase-list.png', 'label' => 'Purchase Order', 'path' => '/purchases', 'caption' => 'Purchase Order ke supplier — Pending/Received, stok auto-update saat barang datang.'],
                    ['type' => 'screenshot', 'file' => 'supplier-list.png', 'label' => 'Daftar Supplier', 'path' => '/suppliers', 'caption' => 'Supplier management — nama, kontak, alamat, produk yang disupply.'],

                    ['type' => 'h', 'text' => 'Invoice, Gate Pass & Keuangan'],
                    ['type' => 'screenshot', 'file' => 'invoice-list.png', 'label' => 'Daftar Invoice', 'path' => '/invoices', 'caption' => 'Invoice — Unpaid/Half Paid/Full Paid, PDF, WhatsApp, Email.'],
                    ['type' => 'screenshot', 'file' => 'gate-pass-list.png', 'label' => 'Daftar Gate Pass', 'path' => '/gate-passes', 'caption' => 'Gate Pass — surat jalan + stempel waktu masuk/keluar.'],
                    ['type' => 'screenshot', 'file' => 'income-list.png', 'label' => 'Income', 'path' => '/incomes', 'caption' => 'Income non-operasional — sewa, komisi, pendapatan lain.'],
                    ['type' => 'screenshot', 'file' => 'expense-list.png', 'label' => 'Expense', 'path' => '/expenses', 'caption' => 'Expense — listrik, gaji, sewa, operasional harian.'],

                    ['type' => 'h', 'text' => 'Laporan'],
                    ['type' => 'screenshot', 'file' => 'report-service.png', 'label' => 'Laporan Service', 'path' => '/reports/service', 'caption' => 'Service Report — volume per kategori & teknisi, filter tanggal.'],
                    ['type' => 'screenshot', 'file' => 'report-sales.png', 'label' => 'Laporan Sales', 'path' => '/reports/sales', 'caption' => 'Sales Report — penjualan parts & kendaraan, export Excel/PDF.'],
                    ['type' => 'screenshot', 'file' => 'report-stock.png', 'label' => 'Laporan Stock', 'path' => '/reports/stock', 'caption' => 'Stock Report — stok berjalan, slow-moving items, reorder alert.'],
                    ['type' => 'screenshot', 'file' => 'report-financial.png', 'label' => 'Laporan Financial', 'path' => '/reports/financial', 'caption' => 'Financial Report — profit/loss = revenue - expense, ringkasan bulanan.'],

                    ['type' => 'h', 'text' => 'Marketing & HRM'],
                    ['type' => 'screenshot', 'file' => 'voucher-list.png', 'label' => 'Voucher / Promo', 'path' => '/vouchers', 'caption' => 'Voucher & promo code — diskon, minimum transaksi, masa berlaku.'],
                    ['type' => 'screenshot', 'file' => 'booking-list.png', 'label' => 'Booking Online', 'path' => '/bookings', 'caption' => 'Booking online dari customer — status, assign ke service.'],
                    ['type' => 'screenshot', 'file' => 'commission-list.png', 'label' => 'Komisi Teknisi', 'path' => '/commissions', 'caption' => 'Komisi teknisi per service — hitung otomatis, mark paid batch.'],

                    ['type' => 'h', 'text' => 'Sistem & Keamanan'],
                    ['type' => 'screenshot', 'file' => 'user-list.png', 'label' => 'Manajemen User', 'path' => '/users', 'caption' => 'User management — tambah/hapus user, assign role, status aktif.'],
                    ['type' => 'screenshot', 'file' => 'role-list.png', 'label' => 'Role & Permission', 'path' => '/roles', 'caption' => 'Role-based access control — permission per role.'],
                    ['type' => 'screenshot', 'file' => 'activity-logs.png', 'label' => 'Activity Log', 'path' => '/activity-logs', 'caption' => 'Activity log — semua aksi user tercatat: create, update, delete.'],
                    ['type' => 'screenshot', 'file' => 'payment-gateways.png', 'label' => 'Payment Gateway', 'path' => '/payment-gateways', 'caption' => 'Payment Gateway config — 23 PG preset, pilih format, auto-fill kredensial.'],

                    ['type' => 'h', 'text' => 'Dokumentasi'],
                    ['type' => 'screenshot', 'file' => 'docs-index.png', 'label' => 'Halaman Docs', 'path' => '/docs', 'caption' => 'Halaman dokumentasi publik — tutorial lengkap, demo accounts, alur bisnis.'],
                    ['type' => 'tip', 'text' => 'Screenshot di atas adalah tampilan nyata dari aplikasi yang sedang berjalan. Semua halaman responsive dan bisa diakses dari sidebar.'],
                ],
            ],
        ];
    }
}
