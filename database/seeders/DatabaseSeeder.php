<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CheckoutCategory;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseHistoryRecord;
use App\Models\FuelType;
use App\Models\Color;
use App\Models\Income;
use App\Models\IncomeHistoryRecord;
use App\Models\InspectionPointsLibrary;
use App\Models\Invoice;
use App\Models\JobcardDetail;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\StockRecord;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $this->call(PermissionSeeder::class);
        $this->seedSettings();
        $this->seedCurrency();
        $this->seedBranches();
        $this->seedMasterData();
        $this->seedSuppliers();
        $this->seedCustomers();
        $this->seedVehicles();
        $this->seedProducts();
        $this->seedServices();
        $this->seedInvoices();
        $this->seedFinances();
        $this->seedObservationTypes();
        $this->seedObservationPoints();
        $this->seedInspectionPointsLibrary();
        $this->seedCheckoutCategories();

        $this->command->info('Database seeded successfully!');
    }

    private function seedUsers(): void
    {
        User::firstOrCreate(['email' => 'admin@bengkel.test'], [
            'name' => 'Admin Bengkel',
            'email' => 'admin@bengkel.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        User::firstOrCreate(['email' => 'teknisi@bengkel.test'], [
            'name' => 'Teknisi 1',
            'email' => 'teknisi@bengkel.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'system_name', 'value' => 'Aplikasi Bengkel Terbaik', 'group' => 'general'],
            ['key' => 'address', 'value' => 'Jl. Siliwangi No. 88, Semarang', 'group' => 'general'],
            ['key' => 'phone', 'value' => '024-7612345', 'group' => 'general'],
            ['key' => 'email', 'value' => 'info@bengkelpaten.id', 'group' => 'general'],
            ['key' => 'invoice_prefix', 'value' => 'INV', 'group' => 'invoice'],
            ['key' => 'currency', 'value' => 'IDR', 'group' => 'general'],
        ];
        foreach ($settings as $s) {
            Setting::firstOrCreate(['key' => $s['key']], $s);
        }
    }

    private function seedCurrency(): void
    {
        Currency::firstOrCreate(['code' => 'IDR'], [
            'code' => 'IDR',
            'name' => 'Indonesia Rupiah',
            'symbol' => 'Rp',
            'exchange_rate' => 1,
            'is_default' => true,
        ]);
    }

    private function seedBranches(): void
    {
        $branches = [
            ['code' => 'PST', 'name' => 'Aplikasi Bengkel Terbaik — Pusat Semarang', 'phone' => '024-7612345', 'email' => 'pusat@bengkelpaten.id', 'address' => 'Jl. Siliwangi No. 88, Semarang', 'is_active' => true],
            ['code' => 'UNG', 'name' => 'Aplikasi Bengkel Terbaik — Cabang Ungaran', 'phone' => '024-6921111', 'email' => 'ungaran@bengkelpaten.id', 'address' => 'Jl. Diponegoro No. 12, Ungaran', 'is_active' => true],
            ['code' => 'KDL', 'name' => 'Aplikasi Bengkel Terbaik — Cabang Kendal', 'phone' => '0294-381234', 'email' => 'kendal@bengkelpaten.id', 'address' => 'Jl. Pemuda No. 5, Kendal', 'is_active' => true],
        ];
        foreach ($branches as $b) {
            Branch::firstOrCreate(['code' => $b['code']], $b);
        }
    }

    private function seedMasterData(): void
    {
        $types = ['Mobil', 'Motor', 'Truk', 'Bus', 'Pick Up'];
        foreach ($types as $t) {
            VehicleType::firstOrCreate(['vehicle_type' => $t], ['slug' => Str::slug($t)]);
        }

        $brands = [
            1 => ['Toyota', 'Honda', 'Daihatsu', 'Mitsubishi', 'Suzuki', 'Nissan'],
            2 => ['Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'TVS'],
            3 => ['Hino', 'Isuzu', 'Mitsubishi'],
            4 => ['Mercedes'],
            5 => ['Toyota'],
        ];
        foreach ($brands as $typeId => $names) {
            foreach ($names as $name) {
                VehicleBrand::firstOrCreate([
                    'vehicle_type_id' => $typeId,
                    'vehicle_brand' => $name,
                ]);
            }
        }

        $fuels = ['Bensin', 'Solar / Diesel', 'Pertamax', 'Pertalite', 'Listrik (EV)', 'Hybrid'];
        foreach ($fuels as $f) {
            FuelType::firstOrCreate(['fuel_type' => $f], ['slug' => Str::slug($f)]);
        }

        $colors = ['Putih', 'Hitam', 'Silver / Abu', 'Merah', 'Biru', 'Kuning', 'Hijau', 'Orange'];
        foreach ($colors as $c) {
            Color::firstOrCreate(['color' => $c], ['hex_code' => '#' . str_pad(dechex(crc32($c) & 0xFFFFFF), 6, '0', STR_PAD_LEFT)]);
        }

        $pTypes = ['Oli & Pelumas', 'Filter', 'Sistem Pengapian', 'Sistem Rem', 'Baterai / Aki', 'Cairan', 'Ban & Velg', 'Komponen Mesin', 'Kelistrikan', 'Aksesoris', 'Parts CVT', 'Rantai & Sproket'];
        foreach ($pTypes as $pt) {
            ProductType::firstOrCreate(['type' => $pt], ['slug' => Str::slug($pt)]);
        }

        $units = ['Pcs', 'Liter', 'Set', 'Pasang', 'Botol', 'Gram', 'Meter', 'Roll'];
        foreach ($units as $u) {
            ProductUnit::firstOrCreate(['name' => $u], ['abbreviation' => $u]);
        }

        $payments = ['Cash / Tunai', 'Transfer Bank', 'QRIS', 'GoPay', 'OVO', 'Dana', 'Kartu Debit', 'Kartu Kredit', 'Piutang / Kredit'];
        foreach ($payments as $p) {
            PaymentMethod::firstOrCreate(['payment' => $p], ['slug' => Str::slug($p)]);
        }

        $taxes = [
            ['taxname' => 'PPN 11%', 'tax' => 11.00],
            ['taxname' => 'Tanpa Pajak (0%)', 'tax' => 0.00],
        ];
        foreach ($taxes as $t) {
            TaxRate::firstOrCreate($t);
        }

        $categories = [
            ['repair_category_name' => 'Servis Berkala', 'slug' => 'servis-berkala'],
            ['repair_category_name' => 'Kendaraan Rusak/Mogok', 'slug' => 'kendaraan-rusak'],
            ['repair_category_name' => 'Repeat Job', 'slug' => 'repeat-job'],
            ['repair_category_name' => 'Customer Menunggu', 'slug' => 'customer-menunggu'],
            ['repair_category_name' => 'Klaim Garansi', 'slug' => 'klaim-garansi'],
        ];
        foreach ($categories as $rc) {
            RepairCategory::firstOrCreate(['slug' => $rc['slug']], $rc);
        }
    }

    private function seedSuppliers(): void
    {
        Supplier::firstOrCreate(['email' => 'indoparts@supplier.id'], [
            'name' => 'PT Indoparts Jaya', 'email' => 'indoparts@supplier.id', 'phone' => '024-5678901',
        ]);
    }

    private function seedCustomers(): void
    {
        $customers = [
            ['name' => 'Hendra Wijaya', 'email' => 'hendra@gmail.com', 'phone' => '081234567801', 'address' => 'Jl. Cendana No. 12, Semarang'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@gmail.com', 'phone' => '081234567802', 'address' => 'Perum Griya Indah B-5, Semarang'],
            ['name' => 'Rudi Hartono', 'email' => 'rudi@cvmaju.id', 'phone' => '081234567803', 'address' => 'Jl. Veteran No. 45, Semarang', 'company_name' => 'CV Maju Bersama'],
            ['name' => 'Rina Kusuma', 'email' => 'rina@gmail.com', 'phone' => '081234567804', 'address' => 'Jl. Pandanaran No. 22, Semarang'],
            ['name' => 'Wahyu Nugroho', 'email' => 'wahyu@sinarabadi.id', 'phone' => '081234567805', 'address' => 'Jl. MT Haryono No. 99, Semarang', 'company_name' => 'PT Sinar Abadi'],
        ];
        foreach ($customers as $c) {
            Customer::firstOrCreate(['email' => $c['email']], $c);
        }
    }

    private function seedVehicles(): void
    {
        $vehicles = [
            ['customer_id' => 1, 'vehicle_type_id' => 1, 'vehicle_brand_id' => 1, 'fuel_type_id' => 4, 'number_plate' => 'H 1234 AB', 'model_name' => 'Avanza', 'model_year' => '2019', 'odometer' => 65000],
            ['customer_id' => 2, 'vehicle_type_id' => 1, 'vehicle_brand_id' => 3, 'fuel_type_id' => 4, 'number_plate' => 'H 5678 BC', 'model_name' => 'Xenia', 'model_year' => '2020', 'odometer' => 48000],
            ['customer_id' => 3, 'vehicle_type_id' => 1, 'vehicle_brand_id' => 1, 'fuel_type_id' => 2, 'number_plate' => 'H 9999 CD', 'model_name' => 'Fortuner', 'model_year' => '2018', 'odometer' => 82000],
            ['customer_id' => 4, 'vehicle_type_id' => 2, 'vehicle_brand_id' => 7, 'fuel_type_id' => 1, 'number_plate' => 'H 2468 EF', 'model_name' => 'Beat Street', 'model_year' => '2021', 'odometer' => 12000],
            ['customer_id' => 5, 'vehicle_type_id' => 2, 'vehicle_brand_id' => 8, 'fuel_type_id' => 1, 'number_plate' => 'H 1357 GH', 'model_name' => 'NMAX 155', 'model_year' => '2022', 'odometer' => 8500],
            ['customer_id' => 1, 'vehicle_type_id' => 1, 'vehicle_brand_id' => 2, 'fuel_type_id' => 4, 'number_plate' => 'H 3579 IJ', 'model_name' => 'Brio Satya', 'model_year' => '2020', 'odometer' => 38000],
            ['customer_id' => 3, 'vehicle_type_id' => 1, 'vehicle_brand_id' => 4, 'fuel_type_id' => 2, 'number_plate' => 'H 8642 KL', 'model_name' => 'Pajero Sport', 'model_year' => '2017', 'odometer' => 95000],
            ['customer_id' => 2, 'vehicle_type_id' => 2, 'vehicle_brand_id' => 9, 'fuel_type_id' => 1, 'number_plate' => 'H 7531 MN', 'model_name' => 'GSX-R150', 'model_year' => '2023', 'odometer' => 5200],
        ];
        foreach ($vehicles as $v) {
            Vehicle::firstOrCreate(['number_plate' => $v['number_plate']], $v);
        }
    }

    private function seedProducts(): void
    {
        $products = [
            ['product_no' => 'PRD-001', 'code' => 'OLI-001', 'name' => 'Oli Mesin Shell Helix HX5 10W-40 (1L)', 'product_type_id' => 1, 'unit_id' => 2, 'supplier_id' => 1, 'price' => 65000, 'cost_price' => 55000, 'warranty' => '6 bulan'],
            ['product_no' => 'PRD-002', 'code' => 'OLI-002', 'name' => 'Oli Mesin Castrol GTX 10W-40 (1L)', 'product_type_id' => 1, 'unit_id' => 2, 'supplier_id' => 1, 'price' => 60000, 'cost_price' => 50000, 'warranty' => '6 bulan'],
            ['product_no' => 'PRD-003', 'code' => 'OLI-003', 'name' => 'Oli Gardan Matic Honda (0.8L)', 'product_type_id' => 1, 'unit_id' => 2, 'supplier_id' => 1, 'price' => 45000, 'cost_price' => 38000, 'warranty' => '3 bulan'],
            ['product_no' => 'PRD-004', 'code' => 'OLI-004', 'name' => 'Oli Mesin Yamalube 10W-40 (0.8L)', 'product_type_id' => 1, 'unit_id' => 2, 'supplier_id' => 1, 'price' => 55000, 'cost_price' => 48000, 'warranty' => '6 bulan'],
            ['product_no' => 'PRD-005', 'code' => 'OLI-005', 'name' => 'Oli Transmisi ATF Dexron III (1L)', 'product_type_id' => 1, 'unit_id' => 2, 'supplier_id' => 1, 'price' => 85000, 'cost_price' => 70000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-006', 'code' => 'FLT-001', 'name' => 'Filter Oli Honda Beat/Vario', 'product_type_id' => 2, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 35000, 'cost_price' => 28000],
            ['product_no' => 'PRD-007', 'code' => 'FLT-002', 'name' => 'Filter Oli Toyota Avanza', 'product_type_id' => 2, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 45000, 'cost_price' => 37000],
            ['product_no' => 'PRD-008', 'code' => 'FLT-003', 'name' => 'Filter Udara Honda Beat', 'product_type_id' => 2, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 40000, 'cost_price' => 32000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-009', 'code' => 'FLT-004', 'name' => 'Filter Udara Toyota Avanza', 'product_type_id' => 2, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 55000, 'cost_price' => 45000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-010', 'code' => 'FLT-005', 'name' => 'Filter Kabin Toyota Avanza', 'product_type_id' => 2, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 75000, 'cost_price' => 60000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-011', 'code' => 'ZAP-001', 'name' => 'Busi NGK Standar (pcs)', 'product_type_id' => 3, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 25000, 'cost_price' => 18000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-012', 'code' => 'ZAP-002', 'name' => 'Busi NGK Iridium (pcs)', 'product_type_id' => 3, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 85000, 'cost_price' => 70000, 'warranty' => '2 tahun'],
            ['product_no' => 'PRD-013', 'code' => 'ZAP-003', 'name' => 'Busi Denso Iridium (pcs)', 'product_type_id' => 3, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 90000, 'cost_price' => 75000, 'warranty' => '2 tahun'],
            ['product_no' => 'PRD-014', 'code' => 'BRK-001', 'name' => 'Kampas Rem Depan Honda Beat (pasang)', 'product_type_id' => 4, 'unit_id' => 4, 'supplier_id' => 1, 'price' => 85000, 'cost_price' => 65000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-015', 'code' => 'BRK-002', 'name' => 'Kampas Rem Depan Toyota Avanza (set)', 'product_type_id' => 4, 'unit_id' => 3, 'supplier_id' => 1, 'price' => 185000, 'cost_price' => 150000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-016', 'code' => 'BRK-003', 'name' => 'Kampas Rem Belakang Toyota Avanza (set)', 'product_type_id' => 4, 'unit_id' => 3, 'supplier_id' => 1, 'price' => 165000, 'cost_price' => 135000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-017', 'code' => 'BRK-004', 'name' => 'Kampas Rem Tromol Honda Beat (pasang)', 'product_type_id' => 4, 'unit_id' => 4, 'supplier_id' => 1, 'price' => 75000, 'cost_price' => 60000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-018', 'code' => 'AKI-001', 'name' => 'Aki GS Astra MF 35Ah (Mobil)', 'product_type_id' => 5, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 650000, 'cost_price' => 550000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-019', 'code' => 'AKI-002', 'name' => 'Aki GS Astra MF 45Ah (Mobil)', 'product_type_id' => 5, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 750000, 'cost_price' => 650000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-020', 'code' => 'AKI-003', 'name' => 'Aki Motor Yuasa MF 5Ah', 'product_type_id' => 5, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 195000, 'cost_price' => 165000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-021', 'code' => 'CVT-001', 'name' => 'V-Belt Honda Beat / Vario', 'product_type_id' => 11, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 145000, 'cost_price' => 120000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-022', 'code' => 'CVT-002', 'name' => 'Roller Honda Beat 12g (set)', 'product_type_id' => 11, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 55000, 'cost_price' => 45000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-023', 'code' => 'CVT-003', 'name' => 'Kampas Ganda Honda Beat', 'product_type_id' => 11, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 120000, 'cost_price' => 100000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-024', 'code' => 'CVT-004', 'name' => 'V-Belt Yamaha NMAX / Aerox', 'product_type_id' => 11, 'unit_id' => 1, 'supplier_id' => 1, 'price' => 185000, 'cost_price' => 155000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-025', 'code' => 'RNT-001', 'name' => 'Rantai + Sproket Honda Beat (set)', 'product_type_id' => 12, 'unit_id' => 3, 'supplier_id' => 1, 'price' => 185000, 'cost_price' => 155000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-026', 'code' => 'RNT-002', 'name' => 'Rantai + Sproket Yamaha Jupiter (set)', 'product_type_id' => 12, 'unit_id' => 3, 'supplier_id' => 1, 'price' => 195000, 'cost_price' => 165000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-027', 'code' => 'CAI-001', 'name' => 'Air Aki / Akuades (1L)', 'product_type_id' => 6, 'unit_id' => 2, 'supplier_id' => 1, 'price' => 8000, 'cost_price' => 5000],
            ['product_no' => 'PRD-028', 'code' => 'CAI-002', 'name' => 'Minyak Rem DOT 3 (200ml)', 'product_type_id' => 6, 'unit_id' => 5, 'supplier_id' => 1, 'price' => 25000, 'cost_price' => 18000, 'warranty' => '1 tahun'],
            ['product_no' => 'PRD-029', 'code' => 'CAI-003', 'name' => 'Air Radiator / Coolant (1L)', 'product_type_id' => 6, 'unit_id' => 2, 'supplier_id' => 1, 'price' => 35000, 'cost_price' => 28000, 'warranty' => '2 tahun'],
        ];

        $stockData = [
            'OLI-001' => 50, 'OLI-002' => 40, 'OLI-003' => 30, 'OLI-004' => 35,
            'OLI-005' => 20, 'FLT-001' => 40, 'FLT-002' => 30, 'FLT-003' => 25,
            'FLT-004' => 20, 'FLT-005' => 15, 'ZAP-001' => 60, 'ZAP-002' => 40,
            'ZAP-003' => 30, 'BRK-001' => 20, 'BRK-002' => 15, 'BRK-003' => 15,
            'BRK-004' => 20, 'AKI-001' => 8, 'AKI-002' => 6, 'AKI-003' => 12,
            'CVT-001' => 15, 'CVT-002' => 20, 'CVT-003' => 15, 'CVT-004' => 12,
            'RNT-001' => 10, 'RNT-002' => 8, 'CAI-001' => 50, 'CAI-002' => 25,
            'CAI-003' => 20,
        ];

        foreach ($products as $p) {
            $product = Product::firstOrCreate(['code' => $p['code']], $p);
            StockRecord::firstOrCreate(
                ['product_id' => $product->id],
                [
                    'product_id' => $product->id,
                    'supplier_id' => $p['supplier_id'],
                    'quantity' => $stockData[$p['code']] ?? 0,
                ]
            );
        }
    }

    private function seedServices(): void
    {
        $services = [
            ['customer_id' => 1, 'vehicle_id' => 1, 'repair_category_id' => 1, 'title' => 'Servis Berkala 10.000 km Avanza', 'service_date' => '2026-04-01 08:00:00', 'done_status' => 2, 'charge' => 185000, 'description' => 'Ganti oli Shell HX5, filter oli, cek rem & ban', 'is_approved' => true],
            ['customer_id' => 2, 'vehicle_id' => 2, 'repair_category_id' => 1, 'title' => 'Servis Berkala Xenia', 'service_date' => '2026-04-03 09:00:00', 'done_status' => 2, 'charge' => 75000, 'description' => 'Ganti oli mesin, cek kaki-kaki, cek tekanan ban', 'is_approved' => true],
            ['customer_id' => 3, 'vehicle_id' => 3, 'repair_category_id' => 2, 'title' => 'Ganti Kampas Rem + Busi Fortuner', 'service_date' => '2026-04-05 10:00:00', 'done_status' => 2, 'charge' => 235000, 'description' => 'Ganti kampas rem depan belakang, ganti 4 busi NGK', 'is_approved' => true],
            ['customer_id' => 4, 'vehicle_id' => 4, 'repair_category_id' => 1, 'title' => 'Servis CVT Honda Beat', 'service_date' => '2026-04-07 08:30:00', 'done_status' => 2, 'charge' => 125000, 'description' => 'Bersih CVT, ganti roller & kampas ganda Honda Beat', 'is_approved' => true],
            ['customer_id' => 5, 'vehicle_id' => 5, 'repair_category_id' => 1, 'title' => 'Servis Berkala + Ganti Aki NMAX', 'service_date' => '2026-04-10 09:00:00', 'done_status' => 2, 'charge' => 700000, 'description' => 'Ganti oli Yamalube, filter, ganti aki Yuasa NMAX', 'is_approved' => true],
            ['customer_id' => 1, 'vehicle_id' => 6, 'repair_category_id' => 3, 'title' => 'Tune Up Brio Satya', 'service_date' => '2026-04-12 10:00:00', 'done_status' => 2, 'charge' => 350000, 'description' => 'Bersih throttle body, ganti filter udara & busi NGK', 'is_approved' => true],
            ['customer_id' => 2, 'vehicle_id' => 8, 'repair_category_id' => 2, 'title' => 'Ganti Kampas Rem GSX-R150', 'service_date' => '2026-04-15 11:00:00', 'done_status' => 2, 'charge' => 85000, 'description' => 'Ganti kampas rem depan & belakang Suzuki GSX-R150', 'is_approved' => true],
            ['customer_id' => 3, 'vehicle_id' => 7, 'repair_category_id' => 2, 'title' => 'Servis AC Pajero Sport', 'service_date' => '2026-04-17 08:00:00', 'done_status' => 2, 'charge' => 450000, 'description' => 'Isi freon R134a, bersih kondensor, ganti filter kabin', 'is_approved' => true],
            ['customer_id' => 4, 'vehicle_id' => 4, 'repair_category_id' => 1, 'title' => 'Ganti Oli + Filter Honda Beat', 'service_date' => '2026-04-20 09:30:00', 'done_status' => 2, 'charge' => 110000, 'description' => 'Ganti oli Shell HX5 & filter oli Honda Beat', 'is_approved' => true],
            ['customer_id' => 5, 'vehicle_id' => 5, 'repair_category_id' => 1, 'title' => 'Servis Berkala NMAX 20.000 km', 'service_date' => '2026-04-22 10:00:00', 'done_status' => 2, 'charge' => 285000, 'description' => 'Ganti oli Yamalube, filter, busi iridium, cek rantai', 'is_approved' => true],
            ['customer_id' => 1, 'vehicle_id' => 1, 'repair_category_id' => 2, 'title' => 'Ganti V-Belt CVT Avanza', 'service_date' => '2026-04-25 08:00:00', 'done_status' => 2, 'charge' => 195000, 'description' => 'Ganti V-Belt & roller Toyota Avanza di 65.000 km', 'is_approved' => true],
            ['customer_id' => 2, 'vehicle_id' => 2, 'repair_category_id' => 1, 'title' => 'Spooring & Balancing Xenia', 'service_date' => '2026-04-28 09:00:00', 'done_status' => 1, 'charge' => 300000, 'description' => 'Spooring balancing 4 roda, cek ball joint & tie rod', 'is_approved' => false],
            ['customer_id' => 3, 'vehicle_id' => 3, 'repair_category_id' => 2, 'title' => 'Ganti Aki Toyota Fortuner', 'service_date' => '2026-04-29 10:00:00', 'done_status' => 1, 'charge' => 680000, 'description' => 'Ganti aki GS MF 45Ah Toyota Fortuner', 'is_approved' => false],
            ['customer_id' => 4, 'vehicle_id' => 4, 'repair_category_id' => 1, 'title' => 'Servis Berkala Honda Beat 16rb km', 'service_date' => '2026-04-30 08:30:00', 'done_status' => 1, 'charge' => 95000, 'description' => 'Ganti oli, filter, bersih karbu Honda Beat', 'is_approved' => false],
            ['customer_id' => 5, 'vehicle_id' => 5, 'repair_category_id' => 1, 'title' => 'Tune Up NMAX + Busi Iridium', 'service_date' => '2026-04-30 09:00:00', 'done_status' => 1, 'charge' => 120000, 'description' => 'Tune up lengkap NMAX 155, ganti busi Denso iridium', 'is_approved' => false],
        ];

        foreach ($services as $i => $s) {
            $service = Service::create(array_merge($s, [
                'job_no' => 'BP-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'created_by' => 1,
            ]));

            $vehicle = Vehicle::find($s['vehicle_id']);
            $doneStatus = $s['done_status'];

            $jobcardData = [
                'jobcard_no' => $service->job_no,
                'customer_id' => $s['customer_id'],
                'vehicle_id' => $s['vehicle_id'],
                'odometer_in' => $vehicle->odometer,
                'in_date' => $s['service_date'],
                'done_status' => $doneStatus >= 2 ? 1 : 0,
            ];

            if ($doneStatus >= 2) {
                $jobcardData['odometer_out'] = $vehicle->odometer + rand(100, 1000);
                $jobcardData['out_date'] = date('Y-m-d H:i:s', strtotime($s['service_date'] . ' +' . rand(1, 4) . ' hours'));
                $jobcardData['next_service_date'] = date('Y-m-d', strtotime($s['service_date'] . ' +90 days'));
                $jobcardData['next_service_km'] = $vehicle->odometer + 10000;
            } else {
                $jobcardData['next_service_date'] = date('Y-m-d', strtotime($s['service_date'] . ' +90 days'));
                $jobcardData['next_service_km'] = $vehicle->odometer + 10000;
            }

            $service->jobcardDetail()->create($jobcardData);
        }
    }

    private function seedInvoices(): void
    {
        $services = Service::all();
        foreach ($services as $i => $service) {
            $isPaid = $service->done_status >= 2;
            $invoiceNum = 'INV-2026-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);

            Invoice::create([
                'invoice_number' => $invoiceNum,
                'customer_id' => $service->customer_id,
                'service_id' => $service->id,
                'payment_method_id' => $isPaid ? rand(1, 3) : ($i % 3 == 0 ? 2 : 1),
                'payment_status' => $isPaid ? 2 : 0,
                'total_amount' => $service->charge,
                'grand_total' => $service->charge,
                'paid_amount' => $isPaid ? $service->charge : 0,
                'amount_received' => $isPaid ? $service->charge : 0,
                'invoice_date' => $service->service_date->format('Y-m-d'),
                'invoice_type' => 'service',
                'created_by' => 1,
            ]);
        }
    }

    private function seedFinances(): void
    {
        $paidInvoices = Invoice::where('payment_status', 2)->get();

        $incomeLabels = [
            'Servis Berkala Avanza', 'Servis Berkala Xenia', 'Ganti Kampas Rem Fortuner',
            'Servis CVT Honda Beat', 'Servis Berkala NMAX + Aki', 'Tune Up Brio Satya',
            'Ganti Kampas Rem GSX-R150', 'Servis AC Pajero Sport', 'Ganti Oli Honda Beat',
            'Servis Berkala NMAX 20rb', 'Ganti V-Belt Avanza',
        ];

        foreach ($paidInvoices as $idx => $invoice) {
            $income = Income::create([
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer_id,
                'payment_method_id' => $invoice->payment_method_id,
                'amount' => $invoice->grand_total,
                'income_date' => $invoice->invoice_date,
                'label' => $incomeLabels[$idx] ?? 'Pembayaran Invoice ' . $invoice->invoice_number,
                'created_by' => 1,
            ]);

            IncomeHistoryRecord::create([
                'income_id' => $income->id,
                'amount' => $income->amount,
                'label' => $income->label,
            ]);
        }

        $expenses = [
            ['expense_date' => '2026-04-01', 'amount' => 4500000, 'label' => 'Gaji Karyawan April 2026'],
            ['expense_date' => '2026-04-05', 'amount' => 850000, 'label' => 'Tagihan Listrik PLN April'],
            ['expense_date' => '2026-04-05', 'amount' => 3500000, 'label' => 'Sewa Gedung Bengkel April'],
            ['expense_date' => '2026-04-08', 'amount' => 2750000, 'label' => 'Pembelian Oli & Spare Parts'],
            ['expense_date' => '2026-04-10', 'amount' => 250000, 'label' => 'Internet & Telepon'],
            ['expense_date' => '2026-04-10', 'amount' => 85000, 'label' => 'PDAM / Air'],
            ['expense_date' => '2026-04-15', 'amount' => 650000, 'label' => 'Peralatan Servis Baru'],
            ['expense_date' => '2026-04-20', 'amount' => 125000, 'label' => 'Sabun & Perlengkapan Kebersihan'],
        ];

        foreach ($expenses as $e) {
            $expense = Expense::create(array_merge($e, ['created_by' => 1]));

            ExpenseHistoryRecord::create([
                'expense_id' => $expense->id,
                'amount' => $expense->amount,
                'label' => $expense->label,
            ]);
        }
    }

    private function seedObservationTypes(): void
    {
        $types = [
            'Pemeriksaan Eksterior',
            'Pemeriksaan Interior',
            'Pemeriksaan Mesin',
            'Pemeriksaan Kolong / Kaki-Kaki',
            'Pemeriksaan Lampu & Kelistrikan',
            'Test Drive',
        ];
        foreach ($types as $t) {
            ObservationType::firstOrCreate(['observation_type' => $t]);
        }
    }

    private function seedObservationPoints(): void
    {
        $points = [
            1 => ['Body / Cat (lecet, penyok, karat)', 'Kaca depan (retak, baret)', 'Kaca samping & belakang', 'Spion kiri & kanan', 'Wiper depan & belakang', 'Ban & velg (aus, tekanan)', 'Pintu & engsel'],
            2 => ['Dashboard / panel instrumen', 'Setir & klakson', 'Sabuk pengaman', 'Jok & sandaran', 'AC & pemanas', 'Audio / head unit', 'Power window & central lock'],
            3 => ['Oli mesin (level, warna)', 'Filter oli', 'Air radiator / coolant', 'Filter udara', 'Busi / koil', 'Belt / fan belt', 'Engine mounting', 'Suara mesin (getaran, knocking)'],
            4 => ['Shock absorber (kebocoran)', 'Ball joint & tie rod', 'Rubber boot / karet boot', 'Knalpot & mounting', 'Gardan / differential', 'Drive shaft / axle', 'Sistem rem (selang, master)'],
            5 => ['Lampu depan (low & high beam)', 'Lampu sein depan & belakang', 'Lampu rem', 'Lampu mundur', 'Lampu hazard', 'Lampu kabut / fog lamp', 'Aki & terminal', 'Klason'],
            6 => ['Akselerasi / tarikan mesin', 'Rem (pakem, bunyi, getar)', 'Transmisi (halus, selip)', 'Stir / setir (steady, oblak)', 'Suara tidak normal saat jalan', 'Suspensi & kenyamanan'],
        ];
        foreach ($points as $typeId => $items) {
            foreach ($items as $pt) {
                ObservationPoint::firstOrCreate([
                    'observation_type_id' => $typeId,
                    'observation_point' => $pt,
                ]);
            }
        }
    }

    private function seedInspectionPointsLibrary(): void
    {
        $library = [
            1 => [
                ['point' => 'Body - Panel depan', 'category' => 'eksterior'],
                ['point' => 'Body - Panel samping', 'category' => 'eksterior'],
                ['point' => 'Body - Panel belakang', 'category' => 'eksterior'],
                ['point' => 'Kaca - Retak/Baret', 'category' => 'eksterior'],
                ['point' => 'Ban - Keausan', 'category' => 'eksterior'],
                ['point' => 'Spion - Kondisi', 'category' => 'eksterior'],
            ],
            2 => [
                ['point' => 'Dashboard - Indikator', 'category' => 'interior'],
                ['point' => 'Setir - Kondisi', 'category' => 'interior'],
                ['point' => 'Jok - Robek/Lecet', 'category' => 'interior'],
                ['point' => 'AC - Suhu & Tiupan', 'category' => 'interior'],
                ['point' => 'Audio - Fungsi', 'category' => 'interior'],
            ],
            3 => [
                ['point' => 'Oli - Level', 'category' => 'mesin'],
                ['point' => 'Oli - Kebocoran', 'category' => 'mesin'],
                ['point' => 'Coolant - Level', 'category' => 'mesin'],
                ['point' => 'Filter Udara - Kotor', 'category' => 'mesin'],
                ['point' => 'Busi - Kondisi', 'category' => 'mesin'],
                ['point' => 'Belt - Retak/Aus', 'category' => 'mesin'],
                ['point' => 'Suara Mesin - Normal/Tidak', 'category' => 'mesin'],
            ],
            4 => [
                ['point' => 'Shock - Kebocoran', 'category' => 'kaki-kaki'],
                ['point' => 'Ball Joint - Obak', 'category' => 'kaki-kaki'],
                ['point' => 'Karet Boot - Robek', 'category' => 'kaki-kaki'],
                ['point' => 'Knalpot - Bocor/Berisik', 'category' => 'kaki-kaki'],
            ],
            5 => [
                ['point' => 'Lampu Depan - Low Beam', 'category' => 'kelistrikan'],
                ['point' => 'Lampu Depan - High Beam', 'category' => 'kelistrikan'],
                ['point' => 'Lampu Sein - Semua', 'category' => 'kelistrikan'],
                ['point' => 'Lampu Rem', 'category' => 'kelistrikan'],
                ['point' => 'Aki - Tegangan', 'category' => 'kelistrikan'],
                ['point' => 'Klason - Volume', 'category' => 'kelistrikan'],
            ],
            6 => [
                ['point' => 'Akselerasi - Responsif', 'category' => 'test-drive'],
                ['point' => 'Rem - Pakem & Lurus', 'category' => 'test-drive'],
                ['point' => 'Transmisi - Perpindahan Gigi', 'category' => 'test-drive'],
                ['point' => 'Suspensi - Bunyi & Getaran', 'category' => 'test-drive'],
            ],
        ];

        foreach ($library as $typeId => $items) {
            foreach ($items as $item) {
                InspectionPointsLibrary::firstOrCreate([
                    'observation_type_id' => $typeId,
                    'point' => $item['point'],
                ], array_merge($item, ['sort_order' => 0, 'is_active' => true]));
            }
        }
    }

    private function seedCheckoutCategories(): void
    {
        $categories = [
            'Oli & Cairan', 'Kondisi Mesin', 'Rem & Kaki-kaki',
            'Kelistrikan', 'Body & Kabin', 'Test Drive',
        ];

        foreach ($categories as $cat) {
            CheckoutCategory::firstOrCreate(['category_name' => $cat]);
        }
    }
}
