<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DocsController extends Controller
{
    public function index(): View
    {
        return view('docs.index', [
            'sections' => $this->sections(),
            'appName'  => config('app.name', config('app.name')),
        ]);
    }

    public function show(string $slug): View
    {
        $sections = $this->sections();

        $section = collect($sections)->firstWhere('slug', $slug);
        abort_unless($section, 404);

        $appName = config('app.name', config('app.name'));

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
                'lead'  => 'Sekilas tentang Aplikasi Bengkel Terbaik, 15 navigation group, dan alur 13-status workflow.',
                'body'  => [
                    ['type' => 'p', 'text' => 'Aplikasi Bengkel Terbaik adalah aplikasi manajemen bengkel berbasis web dengan 60+ modul terintegrasi. Cakupannya menutup seluruh siklus harian: mulai dari booking online, check-in, inspeksi, approval customer, pengerjaan teknisi, QC, invoicing, pembayaran, sampai serah terima — plus akuntansi otomatis via AutoJournalService.'],
                    ['type' => 'p', 'text' => 'Tutorial ini menjelaskan urut-urutan pemakaian aplikasi seperti operator bekerja di hari pertama: setup data referensi → daftar pelanggan & kendaraan → proses service 13 langkah → tutup invoice → auto-journal → tinjau laporan keuangan (General Ledger, P&L, Balance Sheet).'],
                    ['type' => 'h', 'text' => '15 Navigation Group (Sidebar)'],
                    ['type' => 'ul', 'items' => [
                        '<b>📊 Dashboard</b> — ringkasan bisnis, chart, quick actions, notification bell',
                        '<b>🏭 Operations</b> — Booking Online, Cabang, Customer, Kendaraan, Gate Passes',
                        '<b>🔧 Service Management</b> — Service, Jobcard, Subkontraktor, Service Package, Service History',
                        '<b>📦 Inventory</b> — Produk, Stok, Gudang, Supplier, Purchase Order, Peralatan, Purchase Returns, Stock Adjustment',
                        '<b>🛒 Sales & POS</b> — POS Kasir terminal, Invoice, DP, Payment',
                        '<b>👨‍🔧 Technicians</b> — Teknisi, Komisi, Absensi, Gaji',
                        '<b>📢 CRM & Marketing</b> — Customer Group, Voucher, Loyalty, Blog, Campaign, Recall CRM',
                        '<b>🛡️ Warranty</b> — Garansi, Klaim Garansi',
                        '<b>💰 Finance & Accounting</b> — COA, Journal Entry, General Ledger, P&L, Balance Sheet, Kas Kecil, Income/Expense',
                        '<b>📈 Reports</b> — Service, Sales, Stock, Financial, Technician Performance, Customer LTV',
                        '<b>🗂️ Master Data</b> — Vehicle Types, Brands, Fuel, Colors, Product Types, Units, Payment Methods, Tax, Repair Categories, Observation Types/Points, Inspection Points, Checkout Categories, Service Packages',
                        '<b>🌍 Geography</b> — Negara, Provinsi, Kota, Currency',
                        '<b>🔔 Notifications</b> — Template, Antrian, Log, Reminder Service',
                        '<b>👥 Users & Security</b> — User, Role, Permission, API Token, Activity Log, 2FA',
                        '<b>⚙️ Settings</b> — General Settings, Business Hours, Backup & Restore',
                    ]],
                    ['type' => 'h', 'text' => 'Alur ringkas (end-to-end)'],
                    ['type' => 'ol', 'items' => [
                        'Setup awal: <b>Settings</b> profil bengkel, jam operasional, dan <b>Geography</b> (negara, provinsi, kota, currency).',
                        'Isi <b>Master Data</b> lengkap (jenis kendaraan, merk, BBM, warna, kategori produk, satuan, metode bayar, tarif pajak, kategori repair, observation types/points, service packages).',
                        'Daftarkan <b>Customer</b> dan <b>Kendaraan</b> di menu <b>Operations</b>. Customer Group (Fleet) untuk perusahaan dengan banyak kendaraan.',
                        'Buat <b>Booking Online</b> dari customer atau admin di menu <b>Operations → Booking Online</b>. Pilih tanggal & jam, assign teknisi.',
                        'Booking dikonversi ke <b>Service</b> di menu <b>Service Management</b> → otomatis menghasilkan <b>Jobcard</b> + QR Code.',
                        'Alur 13 status: <b>Booked → Checked In → Inspection → Waiting Approval → Approved → In Progress → Waiting Parts → QC → Ready → Invoiced → Paid → Released → Completed</b>.',
                        'Isi <b>Observation / Checklist</b>, tambahkan parts dari <b>Inventory</b> (auto-link stok & auto-berkurang), assign ke <b>Subkontraktor</b> jika perlu.',
                        'Kirim estimasi ke customer untuk <b>Approval via WhatsApp/Email</b> — customer bisa approve/reject.',
                        'Terbitkan <b>Invoice</b> dengan <b>Down Payment (DP)</b>, terima <b>Payment</b> (bisa cicil), kirim via WA/Email.',
                        '<b>Auto-Journal</b>: setiap invoice & payment otomatis generate journal entry (Piutang Usaha, Pendapatan, Kas, PPN) via <b>AutoJournalService</b>.',
                        'Buat <b>Gate Pass</b> dengan <b>Digital Signature</b> customer & teknisi di menu <b>Operations → Gate Passes</b>.',
                        'Tinjau <b>Reports</b>: Service, Sales, Stock, Financial, Technician Performance, Customer LTV.',
                        'Pantau <b>General Ledger, Laba Rugi (P&L), Neraca (Balance Sheet)</b> — auto-generate dari journal.',
                        'Gunakan <b>POS Kasir</b> di menu <b>Sales & POS</b> untuk jual sparepart eceran walk-in.',
                        '<b>Next Service Auto-Reminder</b>: sistem otomatis deteksi kendaraan jatuh tempo service berdasarkan odo/tanggal.',
                        '<b>Notification bell</b> di topbar — real-time unread count, klik untuk lihat semua notifikasi.',
                    ]],
                    ['type' => 'screenshot', 'file' => 'dashboard.png', 'label' => 'Dashboard Utama', 'path' => '/', 'caption' => 'Tampilan dashboard setelah login — ringkasan service, revenue, stok rendah, chart, dan recent jobs.'],
                ],
            ],
            [
                'slug'  => 'persiapan-awal',
                'icon'  => 'fa-rocket',
                'title' => '1. Persiapan Awal & Dashboard',
                'lead'  => 'Login, kenalan dengan dashboard + chart + notification bell + quick actions, lalu setup profil bengkel.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Login'],
                    ['type' => 'ol', 'items' => [
                        'Buka URL aplikasi dan login dengan akun admin.',
                        'Demo accounts: <code>admin@bengkel.test</code> / <code>password</code>. Tersedia juga: manager, kasir, teknisi, sales.',
                        'Setelah login, sistem membuka <b>Dashboard</b> dengan ringkasan bisnis dan chart.',
                    ]],
                    ['type' => 'h', 'text' => 'Dashboard — apa yang bisa dilihat'],
                    ['type' => 'ul', 'items' => [
                        '<b>15-Group Sidebar</b>: navigasi terorganisir dalam 15 group mengikuti alur bisnis (Dashboard, Operations, Service Management, Inventory, Sales & POS, Technicians, CRM & Marketing, Warranty, Finance & Accounting, Reports, Master Data, Geography, Notifications, Users & Security, Settings).',
                        '<b>🔔 Notification Bell</b> di topbar kanan — menampilkan jumlah notifikasi belum dibaca (unread count badge merah). Klik untuk dropdown daftar notifikasi: service ready, pembayaran diterima, reminder service, approval request.',
                        '<b>⚡ Quick Action Buttons</b> di dashboard: [+ Booking] langsung buat booking, [+ Job Card] buat jobcard baru, [+ Invoice] buat invoice baru.',
                        '<b>📊 Stat Cards</b>: Total Service Aktif, Service Selesai Hari Ini, Revenue Hari Ini, Revenue Bulanan, Outstanding Invoice, Stok Rendah.',
                        '<b>📈 Revenue Chart</b>: Bar chart pendapatan vs pengeluaran 30 hari terakhir.',
                        '<b>🥧 Status Pie Chart</b>: Doughnut chart distribusi status service (Booked, In Progress, QC, Ready, dll).',
                        '<b>👤 Per-Role Widgets</b>: Owner melihat Profit Bulanan & Revenue Chart. Manager melihat Pending Approvals. Teknisi melihat Task Saya & Service Hari Ini. Kasir melihat Ringkasan POS Hari Ini.',
                        'Dashboard <b>auto-refresh setiap 60 detik</b> — data selalu real-time.',
                        '<b>Dark Mode Toggle</b> di topbar (ikon bulan/matahari) — tersimpan di localStorage.',
                        '<b>PWA Installable</b>: bisa install ke homescreen HP seperti app native, lengkap dengan offline service worker.',
                    ]],
                    ['type' => 'h', 'text' => 'Setting profil bengkel'],
                    ['type' => 'ol', 'items' => [
                        'Klik menu <b>Settings → General Settings</b>. Isi nama bengkel, alamat, no HP, email.',
                        'Set mata uang default di <b>Geography → Currencies</b> (IDR sudah ter-seed).',
                    ]],
                    ['type' => 'screenshot', 'file' => 'settings.png', 'label' => 'Settings', 'path' => '/settings', 'caption' => 'Halaman General Settings — isi profil bengkel, logo, alamat, dan kontak.'],
                ],
            ],
            [
                'slug'  => 'cabang',
                'icon'  => 'fa-building',
                'title' => '2. Cabang & Jam Operasional',
                'lead'  => 'Multi-cabang: daftar cabang, jam buka, hari libur, di bawah group Operations.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Operations → Cabang → + Tambah Cabang</b>. Isi nama, kode, alamat, telepon, email.',
                        'Set jam operasional per cabang di <b>Business Hours</b> (per hari, jam buka & tutup).',
                        'Tambah hari libur nasional atau cuti bersama.',
                        'Switch cabang aktif dari dropdown topbar — semua data otomatis ter-filter per cabang.',
                    ]],
                ],
            ],
            [
                'slug'  => 'master-data',
                'icon'  => 'fa-database',
                'title' => '3. Master Data Lengkap',
                'lead'  => 'Data referensi yang dipakai berulang. Isi sekali, dipakai selamanya. Berlokasi di group Master Data.',
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
                        ['Service Packages', 'Template paket: Tune-up, Ganti Oli, Rem — auto-fill harga & estimasi'],
                    ]],
                ],
            ],
            [
                'slug'  => 'customer',
                'icon'  => 'fa-users',
                'title' => '4. Manajemen Customer & Fleet',
                'lead'  => 'Cara mendaftarkan pelanggan baru, customer group, import massal, dan histori. Di group Operations.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Tambah customer & group'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Operations → Customer → Add Customer</b>. Isi nama, HP, email (opsional), alamat, NPWP.',
                        '<b>Customer Groups (Fleet)</b> — grup untuk perusahaan dengan banyak kendaraan (Hotel, Taksi, Perusahaan). Satu grup bisa punya 50+ kendaraan, semua ter-group.',
                        'Import massal via CSV template.',
                    ]],
                    ['type' => 'tip', 'text' => 'Customer Group berguna untuk fleet management — tracking seluruh kendaraan dalam satu perusahaan, lihat total spending, dan jadwal service per grup.'],
                ],
            ],
            [
                'slug'  => 'kendaraan',
                'icon'  => 'fa-car',
                'title' => '5. Manajemen Kendaraan',
                'lead'  => 'Setiap kendaraan terikat ke satu customer. Nomor plat unik + KM chart + service history. Di group Operations.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Operations → Vehicle → Add Vehicle</b>. Pilih customer, isi plat, jenis, merk, model, tahun, warna, BBM.',
                        'Upload foto multi-angle (depan, belakang, sisi, kerusakan).',
                        '<b>KM/Odometer Chart</b> — grafik riwayat odometer dari semua service.',
                        '<b>Service History</b> — halaman khusus menampilkan seluruh riwayat service kendaraan: tanggal, km, teknisi, parts terpakai, biaya total.',
                    ]],
                ],
            ],
            [
                'slug'  => 'booking-online',
                'icon'  => 'fa-calendar-check',
                'title' => '5B. Booking Online',
                'lead'  => 'Customer booking service via portal atau admin input manual. Di group Operations.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Operations → Booking Online → + Tambah Booking</b>.',
                        'Pilih customer, kendaraan, tanggal & jam yang diinginkan, assign teknisi.',
                        'Customer bisa booking sendiri via <b>Customer Portal</b> (<code>/customer/booking</code>).',
                        'Booking yang sudah dikonfirmasi bisa langsung dikonversi ke <b>Service</b> dengan satu klik.',
                        'Status booking: Pending → Confirmed → Converted / Cancelled.',
                    ]],
                ],
            ],
            [
                'slug'  => '13-status-workflow',
                'icon'  => 'fa-code-branch',
                'title' => '6. Alur Service (13-Status Workflow)',
                'lead'  => 'Workflow lengkap 13 langkah dari booking sampai serah terima: Booked → Checked In → Inspection → Waiting Approval → Approved → In Progress → Waiting Parts → QC → Ready → Invoiced → Paid → Released → Completed.',
                'body'  => [
                    ['type' => 'h', 'text' => '13 Status Workflow'],
                    ['type' => 'p', 'text' => 'Setiap service melewati 13 tahap yang mencerminkan alur nyata bengkel. Status bergerak maju via tombol <b>Advance</b> di halaman service. Sistem mencatat timestamp di setiap transisi status.'],
                    ['type' => 'table', 'rows' => [
                        ['1 — Booked', 'Booking sudah dibuat (dari Booking Online atau manual). Kendaraan belum masuk bengkel. Bisa langsung dikonversi ke Service.'],
                        ['2 — Checked In', 'Kendaraan sudah masuk bengkel, customer serah terima kunci. Timer mulai berjalan. Teknisi ditugaskan.'],
                        ['3 — Inspection', 'Teknisi melakukan inspeksi menyeluruh: cek kondisi kendaraan, isi observation points & checklist, identifikasi masalah. Bisa upload foto kerusakan.'],
                        ['4 — Waiting Approval', 'Estimasi biaya & pekerjaan sudah disusun. Menunggu persetujuan customer. Customer bisa approve/reject via WhatsApp/Email yang dikirim sistem.'],
                        ['5 — Approved', 'Customer menyetujui estimasi. Teknisi bisa mulai mengerjakan. Jika direject, kembali ke Inspection untuk revisi estimasi.'],
                        ['6 — In Progress', 'Teknisi sedang mengerjakan service. Timer tracking durasi pengerjaan. Bisa tambah parts dari inventory.'],
                        ['7 — Waiting Parts', 'Pengerjaan tertunda karena menunggu sparepart (PO atau transfer dari gudang lain). Status ini mencegah SLA hitungan durasi normal.'],
                        ['8 — QC', 'Quality Control — pemeriksaan akhir sebelum serah terima. Checklist QC: test drive, cek ulang semua point, pastikan tidak ada masalah tersisa.'],
                        ['9 — Ready', 'Kendaraan siap diambil customer. Sistem bisa kirim notifikasi WA/Email ke customer.'],
                        ['10 — Invoiced', 'Invoice sudah diterbitkan (bisa sebelum atau sesudah Ready). DP bisa diminta di tahap ini. Status invoice: dp_paid/full_paid.'],
                        ['11 — Paid', 'Pembayaran lunas diterima (full amount). Bisa satu kali atau cicil. Auto-journal entry ter-generate saat status ini.'],
                        ['12 — Released', 'Kendaraan sudah diserahkan ke customer. Gate Pass dengan digital signature dibuat.'],
                        ['13 — Completed', 'Service selesai penuh. Semua dokumen lengkap: jobcard, invoice, payment, gate pass. Data masuk ke service history kendaraan.'],
                    ]],
                    ['type' => 'h', 'text' => 'Fitur tambahan di workflow'],
                    ['type' => 'ul', 'items' => [
                        '<b>Overdue Label</b>: jika durasi melebihi estimasi, muncul badge OVERDUE merah.',
                        '<b>SLA Timer</b>: hitung durasi dari Check In sampai Ready. Waiting Parts tidak dihitung ke SLA.',
                        '<b>Advance Button</b>: satu tombol untuk maju ke status berikutnya. Sistem validasi kelengkapan data sebelum bisa advance.',
                        '<b>Status History</b>: catatan timestamp setiap transisi status, siapa yang mengubah, dan catatan (opsional).',
                        '<b>Rollback</b>: admin bisa rollback ke status sebelumnya jika ada kesalahan.',
                    ]],
                    ['type' => 'tip', 'text' => '13-status workflow memastikan tidak ada tahap yang terlewat. Setiap status punya validasi data yang harus diisi sebelum bisa advance ke tahap berikutnya.'],
                ],
            ],
            [
                'slug'  => 'service-package',
                'icon'  => 'fa-cubes',
                'title' => '7. Service Package Template',
                'lead'  => 'Template paket service — sekali klik, semua item + harga + estimasi auto terisi. Di Service Management.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Service Management → Service Packages → + Tambah</b>.',
                        'Isi nama paket (Tune-up 40rb, Ganti Oli, Rem Besar), harga, estimasi jam, deskripsi.',
                        'Opsional: isi JSON items untuk auto-fill line item di invoice.',
                        'Di form <b>Add Service</b>, ada dropdown "Paket Service" — pilih paket → auto-fill judul, harga, estimasi jam, dan item invoice.',
                    ]],
                ],
            ],
            [
                'slug'  => 'jobcard',
                'icon'  => 'fa-clipboard-list',
                'title' => '8. Jobcard + QR Code',
                'lead'  => 'Jobcard otomatis terbuat saat service. Print dengan QR Code untuk tracking. Di Service Management.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Service Management → Jobcards</b>, klik nomor jobcard.',
                        'Isi odometer in/out, tanggal, rekomendasi service berikutnya.',
                        'Assign teknisi yang menangani.',
                        '<b>QR Code</b> — print jobcard sekarang ada QR Code yang link ke halaman service detail.',
                        '<b>Conflict Detection</b> — sistem warning jika teknisi sudah punya service di tanggal/jam yang sama.',
                    ]],
                ],
            ],
            [
                'slug'  => 'subcontractor',
                'icon'  => 'fa-user-gear',
                'title' => '9. Subkontraktor',
                'lead'  => 'Tracking pekerjaan yang di-sub ke pihak ketiga: body repair, AC, kelistrikan. Di Service Management.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Service Management → Subkontraktor → + Tambah</b>. Isi nama, spesialisasi, kontak.',
                        'Assign service ke subkontraktor via SubcontractorJob.',
                        'Track biaya, status (assigned/in-progress/done), dan history per subkontraktor.',
                    ]],
                ],
            ],
            [
                'slug'  => 'invoice-payment',
                'icon'  => 'fa-file-invoice',
                'title' => '10. Invoice & Pembayaran + DP',
                'lead'  => 'Invoice 3 tipe, down payment, pembayaran cicil, kirim via WA/Email. Di Sales & POS.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Buat invoice'],
                    ['type' => 'ol', 'items' => [
                        'Dari service/sale, atau buat manual di <b>Sales & POS → Invoice → Add Invoice</b>.',
                        '<b>Product Picker</b> — klik tombol kaca pembesar untuk cari sparepart dari inventory, auto-fill deskripsi + harga + product_id.',
                        '<b>Tambah Jasa Service</b> — tombol terpisah untuk item jasa (labour charge).',
                        '<b>Down Payment (DP)</b> — input DP di form invoice. Status: dp_paid → full_paid.',
                        'Stok otomatis berkurang saat sparepart dipakai di invoice (via Inventory Integration).',
                    ]],
                    ['type' => 'h', 'text' => 'Pembayaran'],
                    ['type' => 'ol', 'items' => [
                        'Buka invoice → klik <b>Tambah Payment</b>. Bisa bayar sebagian (cicil).',
                        'Status: Unpaid → Half Paid → Full Paid (auto).',
                        'Kirim via <b>WhatsApp</b> atau <b>Email</b> langsung dari halaman invoice.',
                        '<b>QRIS Payment Link</b> — generate link pembayaran via payment gateway yang sudah dikonfigurasi.',
                    ]],
                ],
            ],
            [
                'slug'  => 'pos-kasir',
                'icon'  => 'fa-cash-register',
                'title' => '11. POS Kasir + Barcode',
                'lead'  => 'Terminal kasir untuk jual sparepart eceran. Support barcode scan. Di Sales & POS.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Sales & POS → Terminal Kasir</b> → buka sesi.',
                        '<b>Barcode Scan</b> — scan/ketik barcode produk → langsung masuk cart.',
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
                'title' => '12. Gate Pass + Digital Signature',
                'lead'  => 'Surat jalan keluar dengan tanda tangan digital customer & teknisi. Di Operations.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Dari service, generate gate pass di <b>Operations → Gate Passes</b>. Isi jam keluar, nama penjemput.',
                        '<b>Digital Signature</b> — Canvas pad untuk tanda tangan customer & teknisi.',
                        'Touch-screen support (HP/tablet). Simpan sebagai base64 PNG.',
                        'Tanda tangan muncul di halaman gate pass & print.',
                    ]],
                ],
            ],
            [
                'slug'  => 'inventory',
                'icon'  => 'fa-boxes',
                'title' => '13. Inventory, Peralatan & Multi-Gudang',
                'lead'  => 'Produk, stok, supplier, purchase order, peralatan bengkel, multi-gudang + transfer. Di group Inventory.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Produk & Supplier'],
                    ['type' => 'ol', 'items' => [
                        'Tambah produk dengan kode, barcode, harga beli/jual, stok awal.',
                        'Purchase Order → Mark Received → stok auto bertambah + history.',
                        '<b>Barcode</b> — setiap produk bisa punya barcode untuk scan di POS.',
                        '<b>Supplier Price Comparison</b> — bandingkan harga dari beberapa supplier.',
                        '<b>Purchase Returns</b> — retur barang ke supplier, stok auto berkurang dan history tercatat.',
                    ]],
                    ['type' => 'h', 'text' => 'Peralatan Bengkel'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Inventory → Peralatan Bengkel</b>. Daftar alat: lift, scanner, kompresor, tool set.',
                        'Tracking: status (tersedia/dipakai/maintenance/rusak), maintenance date, kategori.',
                        'Maintenance log per alat.',
                    ]],
                    ['type' => 'h', 'text' => 'Multi-Gudang + Transfer'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Inventory → Gudang</b>. Daftar gudang per cabang.',
                        'Setiap gudang punya stok per produk + rak.',
                        '<b>Transfer Stok</b> antar gudang — pilih dari gudang A ke gudang B, stok otomatis pindah.',
                    ]],
                ],
            ],
            [
                'slug'  => 'inventory-integration',
                'icon'  => 'fa-link',
                'title' => '14. Inventory Integration (Stok ↔ Service)',
                'lead'  => 'Barang di inventory langsung terpakai di service. Stok otomatis berkurang, history lengkap.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Cara kerja integrasi stok dengan service'],
                    ['type' => 'ol', 'items' => [
                        'Saat teknisi menambah sparepart di service (via Invoice atau Jobcard), sistem mencari produk dari <b>Inventory</b>.',
                        '<b>Product Picker</b>: klik ikon kaca pembesar di line item — cari produk by nama/kode/barcode. Pilih produk → auto-fill nama, kode, harga jual, dan product_id.',
                        'Setelah item disimpan, <b>stok otomatis berkurang</b> di inventory sesuai quantity yang dipakai.',
                        '<b>Stock History</b> tercatat otomatis: type = <code>service_usage</code>, reference ke service ID. Jadi tahu sparepart mana dipakai di service mana.',
                        'Jika stok tidak mencukupi, sistem memberi <b>warning</b> dan teknisi bisa memilih: tetap pakai (stok jadi minus → perlu stock opname) atau tunda (status service → Waiting Parts).',
                    ]],
                    ['type' => 'h', 'text' => 'Manfaat'],
                    ['type' => 'ul', 'items' => [
                        '<b>Real-time stock accuracy</b>: tidak ada selisih antara stok fisik dan sistem.',
                        '<b>Cost tracking</b>: biaya sparepart langsung terhitung di service cost.',
                        '<b>Reorder alert</b>: saat stok di bawah minimum, muncul notifikasi di dashboard.',
                        '<b>Profit margin</b>: sistem hitung margin dari selisih harga beli (inventory) vs harga jual (invoice).',
                        '<b>Audit trail</b>: setiap sparepart bisa ditelusuri — dari purchase → stok gudang → dipakai di service X → invoice Y.',
                    ]],
                ],
            ],
            [
                'slug'  => 'stock-adjustments',
                'icon'  => 'fa-balance-scale',
                'title' => '15. Stock Adjustments (dengan Approval)',
                'lead'  => 'Koreksi stok dengan workflow approval. Stock opname, stok rusak, stok hilang — semua tercatat.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Alur Stock Adjustment'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Inventory → Stock Adjustment → + Tambah Adjustment</b>.',
                        'Pilih tipe adjustment: <b>Stock Opname</b> (koreksi), <b>Rusak</b>, <b>Hilang</b>, <b>Expired</b>, <b>Lainnya</b>.',
                        'Pilih produk, isi quantity fisik (hasil hitung), sistem otomatis hitung selisih vs stok sistem.',
                        'Isi alasan adjustment (wajib).',
                        'Simpan → status: <b>Pending Approval</b>. Adjustment belum mempengaruhi stok.',
                    ]],
                    ['type' => 'h', 'text' => 'Approval Flow'],
                    ['type' => 'ol', 'items' => [
                        'Manager/Owner mendapat notifikasi adjustment menunggu approval.',
                        'Buka adjustment → review selisih & alasan → <b>Approve</b> atau <b>Reject</b>.',
                        'Jika <b>Approved</b>: stok diperbarui otomatis, stock history tercatat dengan type <code>adjustment</code>.',
                        'Jika <b>Rejected</b>: stok tidak berubah, adjustment ditutup dengan catatan alasan penolakan.',
                        'Semua adjustment (approved/rejected) tercatat di <b>Stock Adjustment History</b> untuk audit.',
                    ]],
                    ['type' => 'tip', 'text' => 'Stock Adjustment wajib approval untuk mencegah manipulasi stok. Semua perubahan stok (baik dari service usage, purchase, transfer, maupun adjustment) tercatat di Stock History — tidak ada yang bisa dihapus.'],
                ],
            ],
            [
                'slug'  => 'keuangan',
                'icon'  => 'fa-chart-line',
                'title' => '16. Keuangan & Akuntansi',
                'lead'  => 'Chart of Accounts, Journal Entry, Income/Expense, Kas Kecil. Di group Finance & Accounting.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Pemasukan & Pengeluaran'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Finance & Accounting → Income / Expense</b>. Catat pemasukan & pengeluaran non-operasional.',
                        '<b>Total Amount</b> — badge total di atas tabel, ikut filter tanggal.',
                    ]],
                    ['type' => 'h', 'text' => 'Kas Kecil (Petty Cash)'],
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Finance & Accounting → Kas Kecil</b>. Catat transaksi kas kecil harian.',
                        'Input kas masuk & kas keluar, saldo otomatis terhitung.',
                        'Setiap transaksi bisa di-link ke Chart of Accounts.',
                    ]],
                    ['type' => 'h', 'text' => 'Chart of Accounts (COA)'],
                    ['type' => 'p', 'text' => 'Buka <b>Finance & Accounting → Chart of Accounts</b>. Setup akun akuntansi: Aset, Liabilitas, Ekuitas, Pendapatan, Beban. Setiap akun punya kode unik (contoh: 1-1000 Kas, 1-1100 Piutang Usaha, 4-1000 Pendapatan Service).'],
                    ['type' => 'h', 'text' => 'Journal Entry'],
                    ['type' => 'p', 'text' => 'Buka <b>Finance & Accounting → Journal Entry</b>. Buat jurnal double-entry: pilih akun debit & kredit, sistem cek balance (debit = kredit). Total otomatis dihitung. Bisa attach bukti transaksi.'],
                    ['type' => 'tip', 'text' => 'COA + Journal Entry adalah fondasi untuk laporan keuangan otomatis: General Ledger, Laba Rugi, Neraca.'],
                ],
            ],
            [
                'slug'  => 'auto-accounting',
                'icon'  => 'fa-robot',
                'title' => '17. Auto Accounting (AutoJournalService)',
                'lead'  => 'Invoice & payment otomatis generate journal entry. General Ledger, Laba Rugi (P&L), dan Neraca (Balance Sheet) auto-terbentuk.',
                'body'  => [
                    ['type' => 'h', 'text' => 'AutoJournalService — cara kerja'],
                    ['type' => 'p', 'text' => '<b>AutoJournalService</b> adalah service class yang otomatis membuat journal entry setiap kali transaksi terjadi. Tidak perlu input manual — sistem mencatat semua transaksi ke buku besar secara real-time.'],
                    ['type' => 'h', 'text' => 'Transaksi yang auto-journal'],
                    ['type' => 'table', 'rows' => [
                        ['Service Invoice diterbitkan', 'Debit: Piutang Usaha | Kredit: Pendapatan Service + PPN Keluaran'],
                        ['Pembayaran diterima', 'Debit: Kas/Bank | Kredit: Piutang Usaha'],
                        ['POS Kasir — Penjualan', 'Debit: Kas | Kredit: Pendapatan Penjualan + PPN Keluaran + Debit: HPP | Kredit: Persediaan'],
                        ['Pembelian sparepart (PO)', 'Debit: Persediaan | Kredit: Utang Usaha'],
                        ['Pembayaran ke supplier', 'Debit: Utang Usaha | Kredit: Kas/Bank'],
                        ['Income/Expense', 'Debit: Kas/Bank | Kredit: Pendapatan Lain-lain (Income) ATAU Debit: Beban | Kredit: Kas (Expense)'],
                    ]],
                    ['type' => 'h', 'text' => 'General Ledger (Buku Besar)'],
                    ['type' => 'p', 'text' => 'Buka <b>Finance & Accounting → General Ledger</b>. Tampilkan semua journal entry dikelompokkan per akun. Filter tanggal, download PDF/Excel. Setiap akun menampilkan saldo awal, mutasi debit/kredit, dan saldo akhir.'],
                    ['type' => 'h', 'text' => 'Laba Rugi (Profit & Loss / Income Statement)'],
                    ['type' => 'p', 'text' => 'Buka <b>Finance & Accounting → Profit & Loss</b>. Laporan laba rugi otomatis: total pendapatan dikurangi total beban = laba/rugi bersih. Filter per bulan/tahun. Bandingkan dengan periode sebelumnya.'],
                    ['type' => 'h', 'text' => 'Neraca (Balance Sheet)'],
                    ['type' => 'p', 'text' => 'Buka <b>Finance & Accounting → Balance Sheet</b>. Laporan posisi keuangan: Aset = Liabilitas + Ekuitas. Auto-generate dari saldo akhir General Ledger. Cocok untuk tutup buku akhir bulan/tahun.'],
                    ['type' => 'tip', 'text' => 'AutoJournalService menghilangkan human error dalam pencatatan akuntansi. Setiap transaksi operasional (service, POS, purchase, payment) langsung tercatat ke buku besar — tidak perlu input jurnal manual. Akuntan hanya perlu review dan tutup buku periodik.'],
                ],
            ],
            [
                'slug'  => 'laporan',
                'icon'  => 'fa-chart-bar',
                'title' => '18. Laporan (7 Jenis)',
                'lead'  => 'Service, Sales, Stock, Financial, Technician Performance, Customer Lifetime Value. Di group Reports.',
                'body'  => [
                    ['type' => 'table', 'rows' => [
                        ['Service Report', 'Volume service per kategori & teknisi, filter tanggal & cabang, export PDF/Excel'],
                        ['Sales Report', 'Penjualan parts & jasa per periode, breakdown per produk/kategori'],
                        ['Stock Report', 'Stok berjalan, slow-moving items, reorder alert, stock valuation'],
                        ['Financial Report', 'Profit/Loss bulanan, income vs expense, cash flow summary'],
                        ['Technician Performance', 'Produktivitas per teknisi: job count, revenue generated, avg durasi pengerjaan, chart perbandingan'],
                        ['Customer Lifetime Value', 'Top 20 customer: total spending, jumlah kunjungan, avg per visit, kendaraan terdaftar'],
                        ['Inventory Movement', 'Laporan mutasi stok: masuk (purchase, transfer in, adjustment in) vs keluar (service usage, POS, transfer out, rusak/hilang)'],
                    ]],
                ],
            ],
            [
                'slug'  => 'marketing',
                'icon'  => 'fa-gift',
                'title' => '19. Marketing & CRM: Voucher, Loyalty, Blog, Campaign, Recall',
                'lead'  => 'Tools untuk menarik dan menahan customer. Di group CRM & Marketing.',
                'body'  => [
                    ['type' => 'ul', 'items' => [
                        '<b>Customer Group (Fleet)</b>: Kelola perusahaan dengan banyak kendaraan dalam satu grup.',
                        '<b>Voucher / Promo</b>: Generate kode promo dengan diskon (nominal atau persen), minimal transaksi, masa berlaku, kuota pemakaian.',
                        '<b>Loyalty & Membership</b>: Poin per transaksi, tier Bronze→Silver→Gold→Platinum, leaderboard, reward redemption.',
                        '<b>Blog</b>: Admin CRUD artikel blog + RSS feed + auto sitemap. Artikel tampil di <code>/blog</code> publik.',
                        '<b>Campaign</b>: Bulk WhatsApp / SMS blast ke daftar customer. Pilih channel, tulis pesan (max 1600 char), kirim massal. Antrian di notification_queue, diproses scheduler tiap 5 menit.',
                        '<b>Recall CRM</b>: Deteksi customer yang sudah lama tidak service (3-6 bulan). Kirim reminder/promosi untuk mengajak kembali. Segmentasi berdasarkan kategori service terakhir, total spending, atau tipe kendaraan.',
                        '<b>Review & Rating</b>: Kumpulkan rating dari customer setelah service, publish/hide, admin reply.',
                    ]],
                ],
            ],
            [
                'slug'  => 'notifikasi',
                'icon'  => 'fa-bell',
                'title' => '20. Notifikasi Multi-Channel + Reminder Otomatis',
                'lead'  => 'WhatsApp, Email, SMS — semua via template + notification queue. Plus auto-reminder service.',
                'body'  => [
                    ['type' => 'h', 'text' => 'Notification Bell (Topbar)'],
                    ['type' => 'p', 'text' => 'Ikon lonceng di topbar kanan menampilkan jumlah notifikasi belum dibaca (badge merah). Klik untuk dropdown notifikasi real-time: service ready, pembayaran diterima, approval menunggu, reminder service. Klik "Lihat Semua" untuk ke halaman Notification Log.'],
                    ['type' => 'h', 'text' => 'Template & Channel'],
                    ['type' => 'ul', 'items' => [
                        '<b>Notification Templates</b>: Buat template WA/Email/SMS dengan variabel <code>{customer_name}</code>, <code>{plate}</code>, <code>{next_service_date}</code>.',
                        '<b>WhatsApp Service</b>: Kirim estimasi untuk approval, notifikasi service selesai, reminder pembayaran.',
                        '<b>Email Service</b>: Invoice PDF, laporan, pemberitahuan.',
                        '<b>SMS Service</b>: Reminder STNK, asuransi, service berkala.',
                    ]],
                    ['type' => 'h', 'text' => 'Next Service Auto-Reminder'],
                    ['type' => 'ul', 'items' => [
                        'Sistem otomatis mendeteksi kendaraan yang sudah jatuh tempo service berdasarkan <b>odometer</b> atau <b>tanggal</b> yang diinput teknisi saat service terakhir.',
                        'Reminder terkirim otomatis via WhatsApp/Email H-7, H-3, dan H-1 sebelum jatuh tempo.',
                        'Customer bisa booking langsung dari link di pesan reminder.',
                        'Admin bisa lihat daftar semua reminder di <b>Notifications → Reminder Service</b>.',
                    ]],
                    ['type' => 'h', 'text' => 'Notification Queue'],
                    ['type' => 'p', 'text' => 'Semua notifikasi masuk antrian (whatsapp/email/sms), diproses scheduler tiap 5 menit. Buka <b>Notifications → Antrian</b> untuk lihat status pending/sent/failed. Email log mencatat setiap notifikasi yang terkirim beserta timestamp.'],
                ],
            ],
            [
                'slug'  => 'hrm',
                'icon'  => 'fa-user-tie',
                'title' => '21. Teknisi: Komisi, Absensi, Gaji',
                'lead'  => 'Hitung komisi teknisi otomatis, catat absensi, generate slip gaji. Di group Technicians.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Technicians → Komisi Teknisi</b>. Filter periode, mark paid batch, laporan per teknisi.',
                        '<b>Attendance</b>: Clock-in / clock-out via form atau QR scan.',
                        '<b>Salary</b>: Generate slip gaji bulanan berdasarkan komisi + gaji pokok, mark paid.',
                    ]],
                ],
            ],
            [
                'slug'  => 'warranty',
                'icon'  => 'fa-shield-alt',
                'title' => '21B. Garansi & Klaim',
                'lead'  => 'Kelola garansi sparepart dan klaim garansi customer. Di group Warranty.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Buka <b>Warranty → Garansi</b>. Setup periode garansi per produk atau kategori.',
                        'Jika service dalam masa garansi, sistem otomatis flag dan biaya parts = Rp 0.',
                        '<b>Klaim Garansi</b>: customer ajukan klaim → teknisi verifikasi → approve/reject → jika approve, buat service baru gratis.',
                        'History klaim garansi per customer & per produk.',
                    ]],
                ],
            ],
            [
                'slug'  => 'customer-portal',
                'icon'  => 'fa-mobile-alt',
                'title' => '22. Customer Portal',
                'lead'  => 'Customer bisa login sendiri — lihat invoice, service status, booking, tracking.',
                'body'  => [
                    ['type' => 'p', 'text' => 'Customer bisa akses di <code>/customer/login</code> menggunakan nomor HP + password.'],
                    ['type' => 'ul', 'items' => [
                        '<b>Dashboard</b>: Nama, tier membership, poin loyalty, kendaraan terdaftar.',
                        '<b>Invoice</b>: 10 invoice terbaru + status pembayaran + download PDF.',
                        '<b>Service</b>: 10 service terbaru + status 13-step workflow + badge warna.',
                        '<b>Booking</b>: Form booking online langsung dari portal, pilih tanggal & jam.',
                        '<b>Tracking</b>: <code>/track/{job_no}</code> — lihat progress service real-time + kasih star rating.',
                        '<b>Service History</b>: seluruh riwayat service kendaraan lengkap dengan detail parts, biaya, teknisi.',
                    ]],
                ],
            ],
            [
                'slug'  => 'sistem',
                'icon'  => 'fa-cog',
                'title' => '23. Sistem & Keamanan',
                'lead'  => 'User, role, permission, API tokens, backup, 2FA, dark mode, PWA. Di Users & Security.',
                'body'  => [
                    ['type' => 'ul', 'items' => [
                        '<b>User Management</b>: CRUD user, assign cabang & role, aktif/nonaktif. Di <b>Users & Security → Users</b>.',
                        '<b>Roles & Permissions</b>: 196 permission × 60 modul. 5 role preset: super_admin, admin, manager, kasir, mekanik. Di <b>Users & Security → Roles</b>.',
                        '<b>API Tokens</b>: Generate token untuk integrasi eksternal (mobile app, webhook). Di <b>Users & Security → API Tokens</b>.',
                        '<b>2FA</b>: Two-factor authentication via authenticator app (Google Authenticator / Authy).',
                        '<b>Backup & Restore</b>: Download backup SQL, riwayat backup, clear cache, optimize. Di <b>Settings → Backup & Restore</b>. Backup otomatis tiap jam 02:00 WIB.',
                        '<b>Activity Log</b>: Semua aksi user tercatat — create, update, delete, login, logout. Di <b>Users & Security → Activity Log</b>.',
                        '<b>Dark Mode</b>: Toggle di topbar, tersimpan di localStorage.',
                        '<b>PWA</b>: Install ke homescreen HP, service worker cache untuk offline mode.',
                    ]],
                ],
            ],
            [
                'slug'  => 'license-pairing',
                'icon'  => 'fa-key',
                'title' => '24. License Pairing (Aktivasi)',
                'lead'  => 'Aplikasi dilindungi license whitelabel.co.id — pairing sekali per domain.',
                'body'  => [
                    ['type' => 'ol', 'items' => [
                        'Beli license di https://whitelabel.co.id.',
                        'Buka domain → wizard <code>/__pair</code> muncul otomatis.',
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
                        ['q' => 'Kenapa menu saya tidak muncul semua?', 'a' => 'Role Anda mungkin terbatas. Admin/super_admin punya semua menu. Cek di <b>Users & Security → Roles</b> permission apa yang dimiliki role Anda.'],
                        ['q' => 'Apa perbedaan 13 status workflow dengan yang lama?', 'a' => 'Workflow lama 6 step (Pending→Check In→Progress→QC→Ready→Delivered). Workflow BARU 13 step lebih detail: Booked → Checked In → Inspection → Waiting Approval → Approved → In Progress → Waiting Parts → QC → Ready → Invoiced → Paid → Released → Completed. Setiap tahap mencerminkan kondisi nyata di bengkel.'],
                        ['q' => 'Bagaimana cara auto-accounting bekerja?', 'a' => 'AutoJournalService otomatis generate journal entry setiap kali invoice diterbitkan atau pembayaran diterima. Tidak perlu input manual. Hasilnya bisa dilihat di <b>Finance & Accounting → General Ledger</b>, <b>P&L</b>, dan <b>Balance Sheet</b>.'],
                        ['q' => 'Stok minus, bagaimana?', 'a' => 'Lakukan <b>Stock Adjustment</b> dengan tipe Stock Opname. Input quantity fisik → submit → Manager approve → stok terkoreksi. Cek <b>Stock History</b> untuk lacak transaksi penyebab selisih.'],
                        ['q' => 'Bagaimana cara backup database?', 'a' => 'Buka <b>Settings → Backup & Restore</b>. Download SQL. Backup otomatis tiap jam 02:00 WIB.'],
                        ['q' => 'Apakah support WhatsApp notification?', 'a' => 'Ya. Isi WHATSAPP_API_URL dan WHATSAPP_API_KEY di .env. Gunakan <b>Campaign</b> di <b>CRM & Marketing</b> untuk blast massal. Notifikasi otomatis juga terkirim via WhatsApp untuk estimasi approval, service selesai, dan reminder.'],
                        ['q' => 'Bisa multi-cabang?', 'a' => 'Ya. Tambah cabang di <b>Operations → Cabang</b>, pakai switcher di topbar. Semua data otomatis ter-filter per cabang.'],
                        ['q' => 'Bagaimana transfer stok antar gudang?', 'a' => 'Buka <b>Inventory → Gudang → Transfer Stok</b>. Pilih dari gudang A ke gudang B. Stok otomatis pindah.'],
                        ['q' => 'Apa itu Recall CRM?', 'a' => 'Fitur di <b>CRM & Marketing → Recall CRM</b> yang mendeteksi customer lama tidak service. Kirim reminder/promosi otomatis untuk mengajak kembali. Bisa segmentasi berdasarkan service terakhir, total spending, atau tipe kendaraan.'],
                        ['q' => 'Cara pakai digital signature?', 'a' => 'Buka <b>Operations → Gate Passes</b> → canvas untuk tanda tangan customer & teknisi. Support touch screen. Tersimpan sebagai gambar dan muncul di print gate pass.'],
                        ['q' => 'Bagaimana cara approval estimasi oleh customer?', 'a' => 'Saat service di status <b>Waiting Approval</b>, sistem kirim estimasi via WhatsApp/Email ke customer. Customer bisa klik link untuk approve/reject. Jika approved, status lanjut ke <b>Approved → In Progress</b>.'],
                        ['q' => 'Apa fungsi notification bell di topbar?', 'a' => 'Ikon lonceng menampilkan jumlah notifikasi belum dibaca. Klik untuk lihat daftar notifikasi real-time: service ready, pembayaran diterima, approval request, reminder service.'],
                    ]],
                ],
            ],
        ];
    }
}
