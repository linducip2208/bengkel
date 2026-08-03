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
                    ['type' => 'p', 'text' => 'Bengkel Paten adalah aplikasi manajemen bengkel berbasis web dengan 60+ modul terintegrasi. Cakupannya menutup seluruh siklus harian: mulai dari kendaraan masuk, jobcard & inspeksi, pemakaian sparepart, sampai invoice, pembayaran, akuntansi, dan laporan keuangan.'],
                    ['type' => 'p', 'text' => 'Tutorial ini menjelaskan urut-urutan pemakaian aplikasi seperti operator bekerja di hari pertama: setup data referensi → daftar pelanggan & kendaraan → proses service → tutup invoice → tinjau laporan.'],
                    ['type' => 'h', 'text' => 'Alur ringkas (end-to-end)'],
                    ['type' => 'ol', 'items' => [
                        'Setup awal: <b>Cabang</b>, jam operasional, hari libur, washbay, currency, dan profil bengkel di <b>Settings</b>.',
                        'Isi <b>Master Data</b> lengkap (jenis kendaraan, merk, BBM, warna, kategori produk, satuan, metode bayar, tarif pajak, kategori repair, tipe & point observasi, kategori checkout, service package).',
                        'Setup <b>Geografi</b> (negara, provinsi, kota) bila perlu alamat terstandar.',
                        'Daftarkan <b>Customer</b> dan <b>Kendaraan</b> milik customer tersebut. Bisa juga buat <b>Customer Group (Fleet)</b> untuk perusahaan dengan banyak kendaraan.',
                        'Buat <b>Service</b> baru → pilih dari <b>Service Package</b> untuk auto-fill → otomatis menghasilkan <b>Jobcard</b> + QR Code.',
                        'Advance workflow: Pending → Check In → In Progress → <b>QC</b> → Ready → Delivered.',
                        'Isi <b>Observation / Checklist</b>, tambahkan parts dari <b>Inventory</b>, assign ke <b>Subkontraktor</b> jika perlu.',
                        'Bisa kirim estimasi ke customer untuk <b>Approval via WhatsApp/Email</b>.',
                        'Terbitkan <b>Invoice</b> dengan <b>Down Payment (DP)</b>, terima <b>Payment</b> (bisa cicil), kirim via WA/Email.',
                        'Buat <b>Gate Pass</b> dengan <b>Digital Signature</b> customer & teknisi.',
                        'Tinjau <b>Reports</b> (6 jenis) dan <b>Audit & Log</b> untuk monitoring.',
                        'Gunakan <b>POS Kasir</b> untuk jual sparepart eceran walk-in.',
                        'Catat keuangan di <b>Chart of Accounts & Journal Entry</b> untuk akuntansi lengkap.',
                    ]],
                    ['type' => 'screenshot', 'file' => 'dashboard.png', 'label' => 'Dashboard Utama', 'path' => '/', 'caption' => 'Tampilan dashboard setelah login — ringkasan service, revenue, stok rendah, chart, dan recent jobs.'],
                ],
            ],
            [
                'slug'  => 'persiapan-awal',
                'icon'  => 'fa-rocket',
                'title' => '1. Persiapan Awal',
                'lead'  => 'Login, kenalan dengan dashboard + chart, lalu setup profil bengkel & jam operasional.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Login'],
                    ['type' => 'ol', 'items' => [
                        'Buka URL aplikasi dan login dengan akun admin.',
                        'Demo accounts: <code>admin@bengkel.test</code> / <code>password</code>. Tersedia juga: manager, kasir, teknisi, sales.',
                        'Setelah login, sistem membuka <b>Dashboard</b> dengan chart revenue & status service.',
                    ]],
                    ['type' => 'h', 'text' => 'Dashboard — apa yang bisa dilihat'],
                    ['type' => 'ul', 'items' => [
                        '<b>6 Stat Cards</b>: Open Services, Completed Today, Today\'s Revenue, Monthly Revenue, Outstanding Invoices, Low Stock Items',
                        '<b>Revenue Chart</b>: Bar chart pendapatan vs pengeluaran 14 hari terakhir',
                        '<b>Status Pie Chart</b>: Doughnut chart Pending / In Progress / Done Today',
                        '<b>Role Widgets</b>: Profit bulanan (owner/admin), Task Saya (semua role)',
                        'Dashboard <b>auto-refresh setiap 60 detik</b>',
                        '<b>Dark Mode Toggle</b> di topbar (ikon bulan/matahari)',
                        '<b>PWA Installable</b>: bisa install ke homescreen HP seperti app native',
                    ]],
                    ['type' => 'h', 'text' => 'Setting profil bengkel'],
                    ['type' => 'ol', 'items' => [
                        'Klik menu <b>Settings → General Settings</b>. Isi nama bengkel, alamat, no HP, email.',
                        'Set mata uang default di <b>Geografi & Currency → Currencies</b> (IDR sudah ter-seed).',
                    ]],
                    ['type' => 'screenshot', 'file' => 'settings.png', 'label' => 'Settings', 'path' => '/settings', 'caption' => 'Halaman General Settings — isi profil bengkel, logo, alamat, dan kontak.'],
                ],
            ],
            [
                'slug'  => 'cabang',
                'icon'  => 'fa-building',
                'title' => '2. Cabang & Jam Operasional',
                'lead'  => 'Multi-cabang: daftar cabang, jam buka, hari libur, dan washbay slot service.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Cabang → Daftar Cabang → + Tambah Cabang</b>. Isi nama, kode, alamat, telepon, email.',
                        'Set jam operasional per cabang di <b>Business Hours</b> (per hari).',
                        'Tambah hari libur nasional atau cuti bersama.',
                        'Switch cabang aktif dari dropdown topbar — semua data otomatis ter-filter.',
                    ]],
                ],
            ],
            [
                'slug'  => 'master-data',
                'icon'  => 'fa-database',
                'title' => '3. Master Data Lengkap',
                'lead'  => 'Data referensi yang dipakai berulang. Isi sekali, dipakai selamanya.',
                'body'  => [
                    ['type' => 'table', 'rows' => [
                        ['Vehicle Types', 'Jenis kendaraan: Mobil, Motor, Truk, Bus, Pick Up'],
                        ['Vehicle Brands', 'Merk per jenis: Toyota, Honda, Yamaha, Suzuki, Hino, dsb'],
                        ['Fuel Types', 'Bensin, Solar, Pertamax, Listrik (EV), Hybrid'],
                        ['Colors', 'Warna kendaraan + hex code'],
                        ['Product Types', 'Kategori sparepart: Oli, Filter, Rem, Aki, CVT, Rantai, dsb'],
                        ['Product Units', 'Pcs, Liter, Set, Pasang, Botol, Gram, Meter, Roll'],
                        ['Payment Methods', 'Cash, Transfer, QRIS, GoPay, OVO, Dana, Kartu Debit/Kredit'],
                        ['Tax Rates', 'PPN 11%, Tanpa Pajak (0%)'],
                        ['Repair Categories', 'Servis Berkala, Rusak/Mogok, Repeat Job, Customer Menunggu, Klaim Garansi'],
                        ['Observation Types', '6 kategori: Eksterior, Interior, Mesin, Kaki-Kaki, Kelistrikan, Test Drive'],
                        ['Observation Points', '40+ point detail per kategori. Dipakai di checklist service'],
                        ['Inspection Points', 'Library 30+ point reusable lintas service'],
                        ['Checkout Categories', 'Kategori final sebelum serah terima kendaraan'],
                        ['Service Packages', 'Template paket: Tune-up, Ganti Oli, Rem — auto-fill harga & estimasi (BARU!)'],
                    ]],
                ],
            ],
            [
                'slug'  => 'customer',
                'icon'  => 'fa-users',
                'title' => '4. Manajemen Customer & Fleet',
                'lead'  => 'Cara mendaftarkan pelanggan baru, customer group, import massal, dan histori.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Tambah customer & group'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Customer → Add Customer</b>. Isi nama, HP, email (opsional), alamat, NPWP.',
                        '<b>BARU: Customer Groups (Fleet)</b> — grup untuk perusahaan dengan banyak kendaraan (Hotel, Taksi, Perusahaan).',
                        'Import massal via CSV template.',
                    ]],
                    ['type' => 'tip', 'text' => 'Customer Group berguna untuk fleet management — satu perusahaan bisa punya 50+ kendaraan, semua ter-group.'],
                ],
            ],
            [
                'slug'  => 'kendaraan',
                'icon'  => 'fa-car',
                'title' => '5. Manajemen Kendaraan',
                'lead'  => 'Setiap kendaraan terikat ke satu customer. Nomor plat unik + KM chart.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Vehicle → Add Vehicle</b>. Pilih customer, isi plat, jenis, merk, model, tahun, warna, BBM.',
                        'Upload foto multi-angle (depan, belakang, sisi, kerusakan).',
                        '<b>BARU: KM/Odometer Chart</b> — grafik riwayat odometer dari semua service.',
                    ]],
                ],
            ],
            [
                'slug'  => 'service-workflow',
                'icon'  => 'fa-tools',
                'title' => '6. Alur Service (6-Step Workflow)',
                'lead'  => 'Workflow lengkap 0→5: Pending → Check In → Progress → QC → Ready → Delivered.',
                'body'  => [
                    ['type' => 'h', 'text' => '6 Step Workflow (BARU!)'],
                    ['type' => 'table', 'rows' => [
                        ['0 — Pending', 'Service baru dibuat, belum masuk'],
                        ['1 — Checked In', 'Kendaraan sudah masuk bengkel, timer mulai'],
                        ['2 — In Progress', 'Teknisi sedang mengerjakan'],
                        ['3 — QC', 'Quality Control — cek sebelum serah terima'],
                        ['4 — Ready', 'Siap diambil customer'],
                        ['5 — Delivered', 'Sudah diserahkan ke customer (Done)'],
                    ]],
                    ['type' => 'h', 'text' => 'Cara pakai'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Service → Add Service</b>, pilih customer & kendaraan.',
                        '<b>BARU: Quick Fill Package</b> — pilih paket (Tune-up, Ganti Oli) → auto-fill judul, harga, estimasi jam.',
                        'Isi <b>Estimasi Jam</b> untuk tracking durasi.',
                        'Saat teknisi mulai, klik <b>Advance</b> → status maju ke step berikutnya.',
                        'Jika melebihi estimasi, muncul label <b>OVERDUE</b> merah.',
                        'Saat selesai, klik <b>Selesai</b> → status jadi Delivered, completed_at tercatat.',
                    ]],
                    ['type' => 'tip', 'text' => 'Gunakan workflow 6-step untuk tracking akurat — tahu persis di tahap mana setiap kendaraan.'],
                ],
            ],
            [
                'slug'  => 'service-package',
                'icon'  => 'fa-cubes',
                'title' => '6B. Service Package Template (BARU)',
                'lead'  => 'Template paket service — sekali klik, semua item + harga + estimasi auto terisi.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Master Data → Service Packages → + Tambah</b>.',
                        'Isi nama paket (Tune-up 40rb, Ganti Oli, Rem Besar), harga, estimasi jam, deskripsi.',
                        'Opsional: isi JSON items untuk auto-fill line item di invoice.',
                        'Di form <b>Add Service</b>, ada dropdown "Paket Service" — pilih paket → auto-fill semua field.',
                    ]],
                ],
            ],
            [
                'slug'  => 'jobcard',
                'icon'  => 'fa-clipboard-list',
                'title' => '7. Jobcard + QR Code (BARU)',
                'lead'  => 'Jobcard otomatis terbuat saat service. Print dengan QR Code untuk tracking.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Service → Jobcards</b>, klik nomor jobcard.',
                        'Isi odometer in/out, tanggal, rekomendasi service berikutnya.',
                        'Assign teknisi yang menangani.',
                        '<b>BARU: QR Code</b> — print jobcard sekarang ada QR Code yang link ke halaman service.',
                        '<b>BARU: Conflict Detection</b> — sistem warning jika teknisi sudah punya service di tanggal sama.',
                    ]],
                ],
            ],
            [
                'slug'  => 'subcontractor',
                'icon'  => 'fa-user-gear',
                'title' => '7B. Subkontraktor (BARU)',
                'lead'  => 'Tracking pekerjaan yang di-sub ke pihak ketiga: body repair, AC, kelistrikan.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Service → Subkontraktor → + Tambah</b>. Isi nama, spesialisasi, kontak.',
                        'Assign service ke subkontraktor via SubcontractorJob.',
                        'Track biaya, status (assigned/done), dan history.',
                    ]],
                ],
            ],
            [
                'slug'  => 'invoice-payment',
                'icon'  => 'fa-file-invoice',
                'title' => '8. Invoice & Pembayaran + DP (BARU)',
                'lead'  => 'Invoice 3 tipe, down payment, pembayaran cicil, kirim via WA/Email.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Buat invoice'],
                    ['type' => 'ol', 'items' => [
                        'Dari service/sale, atau buat manual di <b>Invoice → Add Invoice</b>.',
                        '<b>BARU: Product Picker</b> — klik tombol kaca pembesar untuk cari sparepart dari inventory, auto-fill deskripsi + harga + product_id.',
                        '<b>BARU: Tambah Jasa Service</b> — tombol terpisah untuk item jasa.',
                        '<b>BARU: Down Payment (DP)</b> — input DP di form invoice. Status: dp_paid → full_paid.',
                        'Stok otomatis berkurang saat sparepart dipakai di invoice.',
                    ]],
                    ['type' => 'h', 'text' => 'Pembayaran'],
                    ['type' => 'ol', 'items' => [
                        'Buka invoice → klik <b>Tambah Payment</b>. Bisa bayar sebagian (cicil).',
                        'Status: Unpaid → Half Paid → Full Paid (auto).',
                        'Kirim via <b>WhatsApp</b> atau <b>Email</b> langsung dari halaman invoice.',
                        '<b>BARU: QRIS Payment Link</b> — generate link pembayaran via gateway yang sudah dikonfigurasi.',
                    ]],
                ],
            ],
            [
                'slug'  => 'pos-kasir',
                'icon'  => 'fa-cash-register',
                'title' => '8B. POS Kasir + Barcode (BARU)',
                'lead'  => 'Terminal kasir untuk jual sparepart eceran. Support barcode scan.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>POS Kasir → Terminal Kasir</b> → buka sesi.',
                        '<b>BARU: Barcode Scan</b> — scan/ketik barcode produk → langsung masuk cart.',
                        'Search produk by nama/kode/barcode → Enter untuk auto-add.',
                        'Pilih customer, payment method, uang bayar → checkout.',
                        'Auto: stok berkurang + invoice + payment record + stock history.',
                        'Akhir shift: <b>Tutup Sesi</b> — input saldo akhir, sistem hitung selisih.',
                    ]],
                ],
            ],
            [
                'slug'  => 'gate-pass',
                'icon'  => 'fa-ticket-alt',
                'title' => '9. Gate Pass + Digital Signature (BARU)',
                'lead'  => 'Surat jalan keluar dengan tanda tangan digital customer & teknisi.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Dari service, generate gate pass. Isi jam keluar, nama penjemput.',
                        '<b>BARU: Digital Signature</b> — Canvas pad untuk tanda tangan customer & teknisi.',
                        'Touch-screen support (HP/tablet). Simpan sebagai base64 PNG.',
                        'Tanda tangan muncul di halaman gate pass & print.',
                    ]],
                ],
            ],
            [
                'slug'  => 'inventory',
                'icon'  => 'fa-boxes',
                'title' => '10. Inventory, Peralatan & Multi-Gudang (BARU)',
                'lead'  => 'Produk, stok, supplier, purchase, peralatan bengkel, multi-gudang + transfer.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Produk & Supplier'],
                    ['type' => 'ol', 'items' => [
                        'Tambah produk dengan kode, barcode, harga beli/jual, stok awal.',
                        'Purchase Order → Mark Received → stok auto bertambah + history.',
                        '<b>BARU: Barcode</b> — setiap produk bisa punya barcode untuk scan di POS.',
                        '<b>BARU: Supplier Price Comparison</b> — bandingkan harga dari beberapa supplier.',
                    ]],
                    ['type' => 'h', 'text' => 'Peralatan Bengkel (BARU)'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Inventory → Peralatan Bengkel</b>. Daftar alat: lift, scanner, kompresor, tool set.',
                        'Tracking: status (tersedia/dipakai/maintenance/rusak), maintenance date, kategori.',
                        'Maintenance log per alat.',
                    ]],
                    ['type' => 'h', 'text' => 'Multi-Gudang + Transfer (BARU)'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Inventory → Gudang</b>. Daftar gudang per cabang.',
                        'Setiap gudang punya stok per produk + rak.',
                        '<b>Transfer Stok</b> antar gudang — pilih dari gudang A ke gudang B, stok otomatis pindah.',
                    ]],
                ],
            ],
            [
                'slug'  => 'keuangan',
                'icon'  => 'fa-chart-line',
                'title' => '11. Keuangan + Akuntansi (BARU)',
                'lead'  => 'Income/Expense, Petty Cash, Chart of Accounts, Journal Entry.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Pemasukan & Pengeluaran'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Financial → Income / Expense</b>. Catat pemasukan & pengeluaran non-operasional.',
                        '<b>BARU: Total Amount</b> — badge total di atas tabel, ikut filter tanggal.',
                    ]],
                    ['type' => 'h', 'text' => 'Chart of Accounts (BARU)'],
                    ['type' => 'p', 'text' => 'Buka <b>Financial → Chart of Accounts</b>. Setup akun akuntansi: Aset, Liabilitas, Ekuitas, Pendapatan, Beban. Setiap akun punya kode unik.'],
                    ['type' => 'h', 'text' => 'Journal Entry (BARU)'],
                    ['type' => 'p', 'text' => 'Buka <b>Financial → Journal Entry</b>. Buat jurnal double-entry: pilih akun debit & kredit, sistem cek balance (debit = kredit). Total otomatis dihitung.'],
                    ['type' => 'tip', 'text' => 'CoA + Journal siap untuk laporan Neraca, Laba Rugi, Cash Flow.'],
                ],
            ],
            [
                'slug'  => 'laporan',
                'icon'  => 'fa-chart-bar',
                'title' => '12. Laporan (6 Jenis — BARU)',
                'lead'  => 'Service, Sales, Stock, Financial, Technician Performance, Customer Lifetime Value.',
                'body'  => [
                    ['type' => 'table', 'rows' => [
                        ['Service Report', 'Volume service per kategori & teknisi, filter tanggal, export PDF/Excel'],
                        ['Sales Report', 'Penjualan parts & kendaraan per periode'],
                        ['Stock Report', 'Stok berjalan, slow-moving items, reorder alert'],
                        ['Financial Report', 'Profit/Loss bulanan, income vs expense'],
                        ['Technician Performance (BARU)', 'Produktivitas per teknisi: job count, revenue, avg durasi, chart'],
                        ['Customer Lifetime Value (BARU)', 'Top 20 customer: total spending, kunjungan, avg per visit'],
                    ]],
                ],
            ],
            [
                'slug'  => 'marketing',
                'icon'  => 'fa-gift',
                'title' => '13. Marketing: Voucher, Loyalty, Blog, Campaign (BARU)',
                'lead'  => 'Tools untuk menarik dan menahan customer.',
                'body'  => [
                    ['type' => 'ul', 'items' => [
                        '<b>Voucher / Promo</b>: Generate kode promo dengan diskon, min transaksi, masa berlaku.',
                        '<b>Loyalty & Membership</b>: Poin per transaksi, tier Bronze→Platinum, leaderboard.',
                        '<b>Blog (BARU)</b>: Admin CRUD artikel blog + RSS feed + auto sitemap. Artikel tampil di /blog publik.',
                        '<b>Campaign (BARU)</b>: Bulk WhatsApp / SMS blast ke daftar customer. Pilih channel, tulis pesan (max 1600 char), kirim massal. Antrian di notification_queue, diproses scheduler tiap 5 menit.',
                        '<b>Review & Rating</b>: Kumpulkan rating dari customer, publish/hide, admin reply.',
                    ]],
                ],
            ],
            [
                'slug'  => 'notifikasi',
                'icon'  => 'fa-bell',
                'title' => '14. Notifikasi Multi-Channel (BARU)',
                'lead'  => 'WhatsApp, Email, SMS — semua via template + notification queue.',
                'body'  => [
                    ['type' => 'ul', 'items' => [
                        '<b>Notification Templates</b>: Buat template WA/Email dengan variabel {customer_name}, {plate}, {next_service_date}.',
                        '<b>Reminders</b>: Auto-detect kendaraan jatuh tempo service (odo/tanggal). Kirim batch.',
                        '<b>Notification Queue (BARU)</b>: Semua notifikasi masuk antrian (whatsapp/email/sms), diproses scheduler tiap 5 menit.',
                        '<b>WhatsApp Service (BARU)</b>: Send estimation for approval, send service complete notification.',
                        '<b>SMS Service (BARU)</b>: Send reminder STNK, asuransi, service berkala.',
                        'Email Log: Verifikasi setiap notifikasi yang terkirim.',
                    ]],
                ],
            ],
            [
                'slug'  => 'hrm',
                'icon'  => 'fa-user-tie',
                'title' => '15. HRM: Komisi, Absensi, Gaji',
                'lead'  => 'Hitung komisi teknisi otomatis, catat absensi, generate slip gaji.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>HRM Teknisi → Komisi Teknisi</b>. Filter, mark paid batch, laporan.',
                        'Attendance: Clock-in / clock-out.',
                        'Salary: Generate slip gaji bulanan, mark paid.',
                    ]],
                ],
            ],
            [
                'slug'  => 'customer-portal',
                'icon'  => 'fa-mobile-alt',
                'title' => '16. Customer Portal (BARU Upgrade)',
                'lead'  => 'Customer bisa login sendiri — lihat invoice, service status, booking.',
                'body'  => [
                    ['type' => 'p', 'text' => 'Customer bisa akses di <code>/customer/login</code> menggunakan nomor HP + password.'],
                    ['type' => 'ul', 'items' => [
                        '<b>Dashboard</b>: Nama, tier membership, poin loyalty.',
                        '<b>Invoice</b>: 10 invoice terbaru + status pembayaran.',
                        '<b>Service (BARU)</b>: 10 service terbaru + status workflow (0-5) + badge warna.',
                        '<b>Booking</b>: Form booking online langsung dari portal.',
                        '<b>Tracking</b>: /track/{job_no} — lihat progress + kasih star rating.',
                    ]],
                ],
            ],
            [
                'slug'  => 'sistem',
                'icon'  => 'fa-cog',
                'title' => '17. Sistem & Keamanan (BARU)',
                'lead'  => 'User, role, permission, API tokens, backup, 2FA, dark mode, PWA.',
                'body'  => [
                    ['type' => 'ul', 'items' => [
                        '<b>User Management</b>: CRUD user, assign cabang, aktif/nonaktif.',
                        '<b>Roles & Permissions (BARU)</b>: 196 permission × 60 modul. 5 role preset: super_admin, admin, manager, kasir, mekanik.',
                        '<b>API Tokens (BARU)</b>: Generate token untuk integrasi eksternal (mobile app, webhook).',
                        '<b>2FA</b>: Two-factor authentication via authenticator app.',
                        '<b>Backup & Restore (BARU)</b>: Download backup SQL, riwayat backup, clear cache, optimize.',
                        '<b>Dark Mode (BARU)</b>: Toggle di topbar, tersimpan di localStorage.',
                        '<b>PWA (BARU)</b>: Install ke homescreen HP, service worker cache.',
                        '<b>Activity Log</b>: Semua aksi user tercatat.',
                    ]],
                ],
            ],
            [
                'slug'  => 'license-pairing',
                'icon'  => 'fa-key',
                'title' => '18. License Pairing (Aktivasi)',
                'lead'  => 'Aplikasi dilindungi license whitelabel.co.id — pairing sekali per domain.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Beli license di https://whitelabel.co.id.',
                        'Buka domain → wizard /__pair muncul otomatis.',
                        'Paste Activation Key → submit → sukses.',
                        'Di localhost /*.test, license bypass otomatis.',
                    ]],
                ],
            ],
            [
                'slug'  => 'faq',
                'icon'  => 'fa-question-circle',
                'title' => 'FAQ & Troubleshooting',
                'lead'  => 'Pertanyaan paling sering diajukan operator bengkel.',
                'body'  => [
                    ['type' => 'faq', 'items' => [
                        ['q' => 'Kenapa menu saya tidak muncul semua?', 'a' => 'Role Anda mungkin terbatas. Admin/super_admin punya semua menu. Cek di <b>Roles</b> permission apa yang dimiliki role Anda.'],
                        ['q' => 'Stok minus, bagaimana?', 'a' => 'Lakukan <b>Stock Opname</b> untuk koreksi, lalu cek stock history untuk lacak transaksi penyebab.'],
                        ['q' => 'Bagaimana cara backup database?', 'a' => 'Buka <b>Settings → Backup & Restore</b>. Download SQL. Backup otomatis tiap jam 02:00 WIB.'],
                        ['q' => 'Apakah support WhatsApp notification?', 'a' => 'Ya. Isi WHATSAPP_API_URL dan WHATSAPP_API_KEY di .env. Gunakan Campaign untuk blast massal.'],
                        ['q' => 'Bisa multi-cabang?', 'a' => 'Ya. Tambah cabang di menu Cabang, pakai switcher di topbar. Semua data otomatis ter-filter.'],
                        ['q' => 'Bagaimana transfer stok antar gudang?', 'a' => 'Buka Inventory → Gudang → Transfer Stok. Pilih dari gudang A ke gudang B.'],
                        ['q' => 'Apa itu workflow service 0→5?', 'a' => 'Pending(0) → Check In(1) → Progress(2) → QC(3) → Ready(4) → Delivered(5). Klik Advance untuk maju.'],
                        ['q' => 'Cara pakai digital signature?', 'a' => 'Buka Gate Pass → canvas untuk tanda tangan customer & teknisi. Support touch screen.'],
                    ]],
                ],
            ],
        ];
    }
}
