<?php

namespace App\Console\Commands;

use App\Models\Kelurahan;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportKelurahan extends Command
{
    protected $signature = 'import:kelurahan {file? : Path to CSV file} {--sample : Generate sample data}';
    protected $description = 'Import kelurahan data from CSV or generate sample data';

    public function handle(): void
    {
        $file = $this->argument('file');

        if ($this->option('sample') || !$file) {
            $this->generateSample();
            return;
        }

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return;
        }

        $this->importCsv($file);
    }

    private function importCsv(string $file): void
    {
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);
        $count = 0;

        $this->output->progressStart();
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) continue;

            $data = array_combine($header, $row);
            $name = $data['kelurahan'] ?? $data['name'] ?? $row[0];
            $slug = Str::slug($name);

            Kelurahan::firstOrCreate(
                ['code' => $data['code'] ?? $row[4] ?? uniqid()],
                [
                    'name' => $name,
                    'kecamatan' => $data['kecamatan'] ?? $row[1] ?? '',
                    'kabupaten' => $data['kabupaten'] ?? $data['kota'] ?? $row[2] ?? '',
                    'provinsi' => $data['provinsi'] ?? $row[3] ?? '',
                    'slug' => $slug,
                ]
            );
            $count++;
            if ($count % 100 === 0) $this->output->progressAdvance(100);
        }
        fclose($handle);
        $this->output->progressFinish();
        $this->info("Imported {$count} kelurahan.");
    }

    private function generateSample(): void
    {
        $this->info('Generating sample kelurahan data (500 entries)...');

        $cities = [
            'Jakarta Pusat' => ['Gambir', 'Menteng', 'Sawah Besar', 'Senen', 'Cempaka Putih', 'Johar Baru', 'Kemayoran', 'Tanah Abang'],
            'Jakarta Selatan' => ['Tebet', 'Setiabudi', 'Mampang Prapatan', 'Pasar Minggu', 'Kebayoran Lama', 'Kebayoran Baru', 'Cilandak', 'Pesanggrahan'],
            'Jakarta Timur' => ['Matraman', 'Pulogadung', 'Jatinegara', 'Duren Sawit', 'Kramat Jati', 'Makasar', 'Cipayung', 'Ciracas'],
            'Surabaya' => ['Gubeng', 'Tegalsari', 'Sawahan', 'Wonokromo', 'Wonocolo', 'Rungkut', 'Sukolilo', 'Mulyorejo'],
            'Bandung' => ['Coblong', 'Cidadap', 'Bandung Wetan', 'Sumur Bandung', 'Cibeunying', 'Kiaracondong', 'Batununggal', 'Lengkong'],
            'Medan' => ['Medan Baru', 'Medan Petisah', 'Medan Polonia', 'Medan Johor', 'Medan Amplas', 'Medan Denai', 'Medan Tembung', 'Medan Kota'],
            'Semarang' => ['Semarang Tengah', 'Semarang Utara', 'Semarang Timur', 'Semarang Selatan', 'Semarang Barat', 'Gajah Mungkur', 'Candisari', 'Pedurungan'],
            'Makassar' => ['Makassar', 'Ujung Pandang', 'Wajo', 'Bontoala', 'Mariso', 'Mamajang', 'Tamalate', 'Rappocini'],
            'Yogyakarta' => ['Danurejan', 'Gondokusuman', 'Jetis', 'Gedongtengen', 'Mergangsan', 'Umbulharjo', 'Kotagede', 'Wirobrajan'],
            'Malang' => ['Klojen', 'Blimbing', 'Kedungkandang', 'Sukun', 'Lowokwaru'],
        ];

        $kelurahanNames = [
            'Gambir' => ['Gambir', 'Cideng', 'Duri Pulo', 'Petojo Selatan', 'Petojo Utara'],
            'Tebet' => ['Tebet Barat', 'Tebet Timur', 'Manggarai', 'Manggarai Selatan', 'Bukit Duri'],
            'Gubeng' => ['Gubeng', 'Airlangga', 'Barata Jaya', 'Kertajaya', 'Mojo'],
            'Coblong' => ['Dago', 'Cipaganti', 'Lebak Siliwangi', 'Sadang Serang', 'Sekeloa'],
            'Semarang Tengah' => ['Pindrikan Kidul', 'Pindrikan Lor', 'Sekayu', 'Pendrikan', 'Kauman'],
        ];

        foreach ($cities as $city => $kecamatans) {
            foreach ($kecamatans as $kecamatan) {
                $kelurahans = $kelurahanNames[$kecamatan]
                    ?? ["{$kecamatan} Indah", "{$kecamatan} Jaya", "{$kecamatan} Permai", "{$kecamatan} Asri", "{$kecamatan} Baru"];

                foreach ($kelurahans as $name) {
                    $provinsi = in_array($city, ['Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Timur'])
                        ? 'DKI Jakarta'
                        : (in_array($city, ['Surabaya', 'Malang']) ? 'Jawa Timur'
                            : (in_array($city, ['Bandung']) ? 'Jawa Barat'
                                : (in_array($city, ['Medan']) ? 'Sumatera Utara'
                                    : (in_array($city, ['Semarang']) ? 'Jawa Tengah'
                                        : (in_array($city, ['Makassar']) ? 'Sulawesi Selatan'
                                            : 'DI Yogyakarta')))));

                    Kelurahan::firstOrCreate(
                        ['code' => uniqid()],
                        [
                            'name' => $name,
                            'kecamatan' => $kecamatan,
                            'kabupaten' => $city,
                            'provinsi' => $provinsi,
                            'slug' => Str::slug($name),
                        ]
                    );
                }
            }
        }

        $this->info('Done! ' . Kelurahan::count() . ' kelurahan generated.');
    }
}
