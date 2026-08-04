<?php

namespace App\Support;

/**
 * Programmatic SEO data definitions for Aplikasi Bengkel Terbaik.
 *
 * Target: 1.000.000+ halaman PSEO.
 * -  300.000 halaman: source code / jualan aplikasi bengkel
 * -  700.000 halaman: pattern bengkel umum (kota, brand, service, kombinasi)
 */
class SeoData
{
    // ==================================================================
    // KOTA INDONESIA (514 kota/kabupaten — cukup untuk cross-product)
    // ==================================================================

    /** Kota besar & menengah (100+ kota untuk volume tinggi). */
    public const CITIES_FULL = [
        'jakarta','surabaya','bandung','medan','semarang','makassar','palembang','batam','pekanbaru','bogor',
        'tangerang','bekasi','depok','denpasar','yogyakarta','malang','solo','balikpapan','manado','padang',
        'banjarmasin','samarinda','pontianak','jambi','bengkulu','mataram','kupang','ambon','ternate','jayapura',
        'serang','cilegon','tasikmalaya','cimahi','sukabumi','cirebon','tegal','purwokerto','magelang','salatiga',
        'kediri','blitar','madiun','mojokerto','probolinggo','pasuruan','batu','sidoarjo','gresik','lamongan',
        'bojonegoro','tuban','jember','banyuwangi','lumajang','bondowoso','situbondo','sumenep','pamekasan','bangkalan',
        'sampang','pacitan','ponorogo','trenggalek','tulungagung','nganjuk','ngawi','magetan','wonogiri','klaten',
        'boyolali','sragen','kudus','pati','rembang','blora','grobogan','demak','jepara','kendal',
        'batang','pemalang','pekalongan','brebes','banjar','garut','cianjur','sumedang','indramayu','subang',
        'purwakarta','karawang','pandeglang','lebak','lampung','metropolitan','palu','gorontalo','kendari','bau-bau',
        'bitung','tomohon','tondano','kotamobagu','tahuna','tobelo','tidore','soe','atambua','maumere',
        'ende','bajawa','ruteng','labuan-bajo','waingapu','waikabubak','kalabahi','larantuka','lewoloba','rote',
        'sabu','raijua','alor','lembata','flores','sumba','timor','sorong','manokwari','fakfak',
        'kaimana','teluk-bintuni','teluk-wondama','nabire','serui','biak','merauke','timika','wamena','boven-digoel',
        'asmat','mappi','boven','yahukimo','pegunungan-bintang','tolikara','keerom','supiori','waropen','dogiyai',
        'deiyai','intan-jaya','lanny-jaya','memberamo','nduga','paniai','puncak','puncak-jaya','sarmi','yapen',
        'aceh','binjai','pematang-siantar','tebing-tinggi','tanjung-balai','sibolga','padang-sidempuan','gunungsitoli','dumai','batam-center',
        'tanjung-pinang','pangkal-pinang','sungaipenuh','lubuklinggau','pagar-alam','prabumulih','baturaja','muara-enim','lahat','martapura',
        'tanjung','tabalong','hulu-sungai','barito','kapuas','katingan','kotawaringin','sukamara','lamandau','seruyan',
        'majene','mamuju','pasangkayu','polewali','mamasa','toraja','palopo','parepare','watampone','sinjai',
        'bulukumba','bantaeng','jeneponto','takalar','gowa','maros','pangkep','barru','soppeng','wajo',
    ];

    /** Kota kompak untuk pattern high-volume. */
    public const CITIES_COMPACT = [
        'jakarta','surabaya','bandung','medan','semarang','makassar','palembang','batam','pekanbaru','bogor',
        'tangerang','bekasi','depok','denpasar','yogyakarta','malang','solo','balikpapan','manado','padang',
        'banjarmasin','samarinda','pontianak','jambi','mataram','kupang','ambon','jayapura','serang','cilegon',
        'tasikmalaya','cirebon','tegal','purwokerto','kediri','madiun','sidoarjo','gresik','jember','banyuwangi',
        'lampung','palu','gorontalo','kendari','sorong','manokwari','aceh','batam-center','dumai','martapura',
    ];

    // ==================================================================
    // BRAND MOBIL
    // ==================================================================

    public const CAR_BRANDS = [
        'toyota','honda','daihatsu','suzuki','mitsubishi','nissan','mazda','bmw','mercedes-benz','hyundai',
        'kia','wuling','isuzu','hino','ford','chevrolet','lexus','audi','volkswagen','renault',
        'peugeot','chery','dfsk','tata','mahindra','tesla','byd','gwm','haval','geely',
    ];

    // ==================================================================
    // JENIS SERVICE BENGKEL
    // ==================================================================

    public const SERVICE_TYPES = [
        'servis-berkala','tune-up','ganti-oli','cuci-mobil','body-repair',
        'servis-ac','ganti-ban','spooring-balancing','overhaul-mesin','servis-rem',
        'ganti-aki','servis-kopling','turun-mesin','ganti-kampas','servis-kaki-kaki',
        'repair-transmisi','servis-kelistrikan','ganti-busi','servis-injeksi','cuci-injector',
        'perbaikan-radiator','servis-power-steering','ganti-shockbreaker','ketok-magic','poles-mobil',
        'coating-mobil','kaca-film','ganti-knalpot','modifikasi-mobil','servis-aki-kering',
    ];

    // ==================================================================
    // FITUR / KEYWORD SOURCE CODE
    // ==================================================================

    public const SOURCE_CODE_KEYWORDS = [
        'aplikasi-bengkel','software-bengkel','sistem-informasi-bengkel','program-bengkel',
        'erp-bengkel','aplikasi-service-motor','software-bengkel-mobil','aplikasi-manajemen-bengkel',
        'source-code-aplikasi-bengkel','source-code-bengkel','download-source-code-bengkel',
        'aplikasi-kasir-bengkel','aplikasi-inventory-bengkel','aplikasi-admin-bengkel',
    ];

    public const SC_MODIFIERS = [
        'terbaik','murah','lengkap','gratis-demo','full-source-code',
        'laravel','php','siap-pakai','whitelabel','bisa-custom',
    ];

    public const SC_PRICE_RANGES = [
        '500rb','1jt','2jt','3jt','5jt','7jt','10jt','15jt','gratis','open-source',
    ];

    public const SC_ACTIONS = [
        'beli','jual','download','order','pesan','beli-online',
        'custom','modifikasi','install','pasang','setting','konfigurasi',
    ];

    // ==================================================================
    // GENERATORS: URL LIST — semuanya return array of path strings
    // ==================================================================

    public static function cities(): array { return self::CITIES_FULL; }
    public static function compactCities(): array { return self::CITIES_COMPACT; }
    public static function brands(): array { return self::CAR_BRANDS; }
    public static function services(): array { return self::SERVICE_TYPES; }

    // ── PSEO UMUM ──

    /** /bengkel-{city} */
    public static function allBengkelCityUrls(): array
    {
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            $urls[] = "/bengkel-{$c}";
        }
        return $urls;
    }

    /** /bengkel-{brand}-{city} */
    public static function allBengkelBrandCityUrls(): array
    {
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach (self::CAR_BRANDS as $brand) {
                $urls[] = "/bengkel-{$brand}-{$c}";
            }
        }
        return $urls; // 50 × 30 = 1,500
    }

    /** /service-{service}-{city} */
    public static function allServiceCityUrls(): array
    {
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach (self::SERVICE_TYPES as $svc) {
                $urls[] = "/service-{$svc}-{$c}";
            }
        }
        return $urls; // 50 × 30 = 1,500
    }

    /** /bengkel-terbaik-{city} */
    public static function allBestCityUrls(): array
    {
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            $urls[] = "/bengkel-terbaik-{$c}";
        }
        return $urls; // 50
    }

    /** /bengkel-terbaik-{city}-{year} */
    public static function allBestCityYearUrls(): array
    {
        $urls = [];
        $years = range(2020, (int) date('Y'));
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($years as $y) {
                $urls[] = "/bengkel-terbaik-{$c}-{$y}";
            }
        }
        return $urls; // 50 × ~6 = 300
    }

    /** /harga-service-{service}-{city} */
    public static function allHargaServiceCityUrls(): array
    {
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach (self::SERVICE_TYPES as $svc) {
                $urls[] = "/harga-{$svc}-{$c}";
            }
        }
        return $urls; // 50 × 30 = 1,500
    }

    /** /bengkel-24-jam-{city} */
    public static function all24JamCityUrls(): array
    {
        return array_map(fn($c) => "/bengkel-24-jam-{$c}", self::CITIES_COMPACT);
    }

    /** /bengkel-mobil-{brand} */
    public static function allBengkelBrandUrls(): array
    {
        return array_map(fn($b) => "/bengkel-mobil-{$b}", self::CAR_BRANDS);
    }

    /** /tips-merawat-{brand} */
    public static function allTipsBrandUrls(): array
    {
        return array_map(fn($b) => "/tips-merawat-{$b}", self::CAR_BRANDS);
    }

    /** /perbandingan-{brand-a}-vs-{brand-b} */
    public static function allCompareBrandUrls(): array
    {
        $urls = [];
        $brands = self::CAR_BRANDS;
        for ($i = 0; $i < count($brands); $i++) {
            for ($j = $i + 1; $j < count($brands); $j++) {
                $urls[] = "/perbandingan-{$brands[$i]}-vs-{$brands[$j]}";
            }
        }
        return $urls; // C(30,2) = 435
    }

    /** /alternatif-{service} */
    public static function allAlternatifServiceUrls(): array
    {
        return array_map(fn($s) => "/alternatif-{$s}", self::SERVICE_TYPES);
    }

    // ── MASSIVE EXPANSION ──

    /** Cross: /{bengkel-key}-{city}-{year} */
    public static function allKeywordCityYearUrls(): array
    {
        $kw = ['bengkel','service-mobil','repair-mobil','turun-mesin','body-repair'];
        $years = range(2020, (int) date('Y') + 1);
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($kw as $k) {
                foreach ($years as $y) {
                    $urls[] = "/{$k}-{$c}-{$y}";
                }
            }
        }
        return $urls; // 50 × 5 × ~7 = 1,750
    }

    /** Cross: /{bengkel-key}-{city} */
    public static function allKeywordCityUrls(): array
    {
        $kw = [
            'bengkel-mobil','service-mobil','bengkel-ac-mobil','bengkel-kaki-kaki',
            'bengkel-body-repair','bengkel-kelistrikan','bengkel-transmisi','bengkel-radiator',
            'bengkel-ban','bengkel-velg','bengkel-knalpot','bengkel-modifikasi',
            'bengkel-variasi','bengkel-audio-mobil','bengkel-kaca-mobil','bengkel-salon-mobil',
            'bengkel-detailing','bengkel-starter','bengkel-dinamo','bengkel-turbo',
        ];
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($kw as $k) {
                $urls[] = "/{$k}-{$c}";
            }
        }
        return $urls; // 50 × 20 = 1,000
    }

    /** Cross: /{brand}-{city} */
    public static function allBrandCityUrls2(): array
    {
        $brands = array_slice(self::CAR_BRANDS, 0, 15);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($brands as $b) {
                $k = "bengkel-{$b}";
                $urls[] = "/{$k}-{$c}";
            }
        }
        return $urls; // 200 × 15 = 3,000
    }

    /** /bandingkan-{service}-vs-{service} */
    public static function allCompareServiceUrls(): array
    {
        $urls = [];
        $svcs = self::SERVICE_TYPES;
        for ($i = 0; $i < count($svcs); $i++) {
            for ($j = $i + 1; $j < count($svcs); $j++) {
                $urls[] = "/bandingkan-{$svcs[$i]}-vs-{$svcs[$j]}";
            }
        }
        return $urls; // C(30,2) = 435
    }

    /** /{brand}-{service}-{city} */
    public static function allBrandServiceCityUrls(): array
    {
        $urls = [];
        $brands = array_slice(self::CAR_BRANDS, 0, 10);
        $svcs = array_slice(self::SERVICE_TYPES, 0, 10);
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($brands as $b) {
                foreach ($svcs as $s) {
                    $urls[] = "/{$s}-{$b}-{$c}";
                }
            }
        }
        return $urls; // 50 × 10 × 10 = 5,000
    }

    // ── SOURCE CODE / JUALAN APLIKASI (300K TARGET) ──

    /** /beli-{keyword} */
    public static function allSourceCodeUrls(): array
    {
        $urls = [];
        foreach (self::SOURCE_CODE_KEYWORDS as $kw) {
            $urls[] = "/beli-{$kw}";
        }
        return $urls;
    }

    /** /source-code-{keyword} */
    public static function allSourceCodeDownloadUrls(): array
    {
        $urls = [];
        foreach (self::SOURCE_CODE_KEYWORDS as $kw) {
            $urls[] = "/source-code-{$kw}";
            $urls[] = "/download-{$kw}";
            $urls[] = "/jual-{$kw}";
        }
        return $urls;
    }

    /** /source-code-bengkel-{city} */
    public static function allSourceCodeCityUrls(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (self::SOURCE_CODE_KEYWORDS as $kw) {
                $urls[] = "/{$kw}-{$c}";
            }
        }
        return $urls; // 200 × 14 = 2,800
    }

    /** /source-code-bengkel-{keyword}-{modifier} */
    public static function allSourceCodeBestUrls(): array
    {
        $urls = [];
        foreach (self::SOURCE_CODE_KEYWORDS as $kw) {
            foreach (self::SC_MODIFIERS as $mod) {
                $urls[] = "/{$kw}-{$mod}";
            }
        }
        return $urls; // 14 × 10 = 140
    }

    /** /aplikasi-bengkel-{city}-{price} */
    public static function allSourceCodeCityPriceUrls(): array
    {
        $urls = [];
        $kws = array_slice(self::SOURCE_CODE_KEYWORDS, 0, 5);
        foreach (self::CITIES_FULL as $c) {
            foreach ($kws as $kw) {
                foreach (self::SC_PRICE_RANGES as $p) {
                    $urls[] = "/{$kw}-{$c}-harga-{$p}";
                }
            }
        }
        return $urls; // 200 × 5 × 10 = 10,000
    }

    /** /jual-{action}-{keyword}-{city} */
    public static function allSourceCodeJasaUrls(): array
    {
        $urls = [];
        $kws = array_slice(self::SOURCE_CODE_KEYWORDS, 0, 4);
        foreach (self::CITIES_COMPACT as $c) {
            foreach (self::SC_ACTIONS as $act) {
                foreach ($kws as $kw) {
                    $urls[] = "/{$act}-{$kw}-{$c}";
                }
            }
        }
        return $urls; // 50 × 12 × 4 = 2,400
    }

    /** /paket-{keyword}-{city} */
    public static function allSourceCodePaketUrls(): array
    {
        $urls = [];
        $kws = array_slice(self::SOURCE_CODE_KEYWORDS, 0, 4);
        foreach (self::CITIES_FULL as $c) {
            foreach ($kws as $kw) {
                $prefs = ['paket','harga','demo'];
                foreach ($prefs as $p) {
                    $urls[] = "/{$p}-{$kw}-{$c}";
                }
            }
        }
        return $urls; // 200 × 4 × 3 = 2,400
    }

    /** /bandingkan-{kw-a}-vs-{kw-b} (source code comparison) */
    public static function allSourceCodeVsUrls(): array
    {
        $urls = [];
        $kws = self::SOURCE_CODE_KEYWORDS;
        for ($i = 0; $i < count($kws); $i++) {
            for ($j = $i + 1; $j < count($kws); $j++) {
                $urls[] = "/bandingkan-{$kws[$i]}-vs-{$kws[$j]}";
            }
        }
        return $urls; // C(14,2) = 91
    }

    // ── MASSIVE FILLER (untuk capai 1 juta) ──

    /** Cross masif: /{keyword}-{city}-{year} */
    public static function allMassiveVolumeUrls(): array
    {
        $kw = array_merge(
            ['bengkel','service','repair','tune-up','overhaul','body-repair','ganti-oli','cuci-mobil'],
            ['spooring','balancing','servis-rem','servis-ac','servis-kopling','turun-mesin','modifikasi'],
            ['bengkel-mobil-murah','bengkel-mobil-terdekat','bengkel-rekomendasi','bengkel-profesional']
        );
        $years = range(2018, (int) date('Y') + 2);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($kw as $k) {
                foreach ($years as $y) {
                    $urls[] = "/{$k}-{$c}-{$y}";
                }
            }
        }
        return $urls; // 200 × 19 × ~9 = 34,200
    }

    /** Cross: /{brand}-{city}-{year} */
    public static function allBrandCityYearUrls(): array
    {
        $brands = array_slice(self::CAR_BRANDS, 0, 10);
        $years = range(2020, (int) date('Y') + 1);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($brands as $b) {
                foreach ($years as $y) {
                    $urls[] = "/bengkel-{$b}-{$c}-{$y}";
                }
            }
        }
        return $urls; // 200 × 10 × ~7 = 14,000
    }

    /** /best-{service}-{city} */
    public static function allBestServiceCityUrls(): array
    {
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach (self::SERVICE_TYPES as $svc) {
                $urls[] = "/best-{$svc}-{$c}";
            }
        }
        return $urls; // 50 × 30 = 1,500
    }

    /** /super-mega: {prefix}-{service}-{city}-{suffix} */
    public static function allSuperMegaUrls(): array
    {
        $pref = ['best','top','rekomendasi','murah','terdekat','profesional','berpengalaman','terpercaya','24-jam','express'];
        $suff = ['terbaik','murah','terdekat','terlengkap','bergaransi'];
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach (self::SERVICE_TYPES as $svc) {
                foreach ($pref as $p) {
                    foreach ($suff as $s) {
                        $urls[] = "/{$p}-{$svc}-{$c}-{$s}";
                    }
                }
            }
        }
        return $urls; // 50 × 30 × 10 × 5 = 75,000
    }

    /** Super mega 2: /{brand}-{service}-{city}-{year} */
    public static function allSuperMegaUrls2(): array
    {
        $brands = array_slice(self::CAR_BRANDS, 0, 8);
        $svcs = array_slice(self::SERVICE_TYPES, 0, 8);
        $years = range(2020, (int) date('Y') + 1);
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($brands as $b) {
                foreach ($svcs as $s) {
                    foreach ($years as $y) {
                        $urls[] = "/{$s}-{$b}-{$c}-{$y}";
                    }
                }
            }
        }
        return $urls; // 50 × 8 × 8 × ~7 = 22,400
    }

    /** Full cross source code city × price × modifier */
    public static function allSourceCodeFullCrossUrls(): array
    {
        $kws = array_slice(self::SOURCE_CODE_KEYWORDS, 0, 6);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($kws as $kw) {
                foreach (self::SC_PRICE_RANGES as $p) {
                    foreach (array_slice(self::SC_MODIFIERS, 0, 5) as $mod) {
                        $urls[] = "/{$kw}-{$c}-harga-{$p}-{$mod}";
                    }
                }
            }
        }
        return $urls; // 200 × 6 × 10 × 5 = 60,000
    }

    /** Cross keyword × city × modifier */
    public static function allSourceCodeKwCityModUrls(): array
    {
        $kws = array_slice(self::SOURCE_CODE_KEYWORDS, 0, 8);
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($kws as $kw) {
                foreach (self::SC_MODIFIERS as $mod) {
                    $urls[] = "/{$kw}-{$c}-{$mod}";
                }
            }
        }
        return $urls; // 50 × 8 × 10 = 4,000
    }

    /** Source code feature × city */
    public static function allSourceCodeFeatureUrls(): array
    {
        $features = ['kasir','inventory','service','invoice','customer','laporan','multi-cabang','pos','booking-online','api'];
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($features as $f) {
                $urls[] = "/aplikasi-bengkel-{$f}-{$c}";
                $urls[] = "/source-code-bengkel-{$f}-{$c}";
            }
        }
        return $urls; // 200 × 10 × 2 = 4,000
    }

    /** /bengkel-{city}-harga-{price} */
    public static function allCityPriceUrls(): array
    {
        $prices = ['100rb','200rb','300rb','500rb','1jt','2jt','5jt'];
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($prices as $p) {
                $urls[] = "/bengkel-{$c}-harga-{$p}";
            }
        }
        return $urls; // 200 × 7 = 1,400
    }

    /** /bengkel-{city}-rating-{star} */
    public static function allCityStarUrls(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            for ($star = 1; $star <= 5; $star++) {
                $urls[] = "/bengkel-{$c}-rating-{$star}";
            }
        }
        return $urls; // 200 × 5 = 1,000
    }

    // ── LARGE-SCALE FILLER GROUPS ──

    /** Triple cross: /{brand}-{service}-{city}-{year}-{star} */
    public static function allTripleCrossUrls(): array
    {
        $brands = array_slice(self::CAR_BRANDS, 0, 5);
        $svcs = array_slice(self::SERVICE_TYPES, 0, 5);
        $years = range(2022, (int) date('Y'));
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($brands as $b) {
                foreach ($svcs as $s) {
                    foreach ($years as $y) {
                        $urls[] = "/bengkel-{$b}-{$s}-{$c}-{$y}";
                    }
                }
            }
        }
        return $urls; // 50 × 5 × 5 × ~4 = 5,000
    }

    /** Source code action × kw × city × price */
    public static function allSourceCodeActionPriceUrls(): array
    {
        $actions = ['beli','jual','download','order','custom'];
        $kws = array_slice(self::SOURCE_CODE_KEYWORDS, 0, 5);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($actions as $act) {
                foreach ($kws as $kw) {
                    foreach (array_slice(self::SC_PRICE_RANGES, 0, 5) as $p) {
                        $urls[] = "/{$act}-{$kw}-{$c}-{$p}";
                    }
                }
            }
        }
        return $urls; // 200 × 5 × 5 × 5 = 25,000
    }

    /** City pair comparison /bandingkan-bengkel-{city}-vs-{city} */
    public static function allCompareCityUrls(): array
    {
        $urls = [];
        $cities = self::CITIES_COMPACT;
        for ($i = 0; $i < min(count($cities), 25); $i++) {
            for ($j = $i + 1; $j < min(count($cities), 25); $j++) {
                $urls[] = "/bandingkan-bengkel-{$cities[$i]}-vs-{$cities[$j]}";
            }
        }
        return $urls; // C(25,2) = 300
    }

    /** /bengkel-murah-{city} + /bengkel-terdekat-{city} + /bengkel-express-{city} */
    public static function allShortLabelCityUrls(): array
    {
        $labels = ['murah','terdekat','express','ramah','profesional','terpercaya','lengkap','modern','bersih','nyaman'];
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($labels as $l) {
                $urls[] = "/bengkel-{$l}-{$c}";
            }
        }
        return $urls; // 50 × 10 = 500
    }

    /** Source code district */
    public static function allSourceCodeDistrictUrls(): array
    {
        $districts = [
            'jakarta-pusat','jakarta-selatan','jakarta-barat','jakarta-timur','jakarta-utara',
            'bandung-kota','bandung-barat','surabaya-pusat','surabaya-barat','surabaya-timur',
            'medan-kota','medan-area','semarang-atas','semarang-bawah','depok-margonda',
            'tangerang-kota','tangerang-selatan','bekasi-timur','bekasi-barat','bogor-tengah',
        ];
        $kws = array_slice(self::SOURCE_CODE_KEYWORDS, 0, 4);
        $urls = [];
        foreach ($districts as $d) {
            foreach ($kws as $kw) {
                $urls[] = "/{$kw}-{$d}";
            }
        }
        return $urls; // 20 × 4 = 80
    }

    /** Large filler: /{brand}-{city}-{service}-{year} with expanded brands/cities */
    public static function allBrandCityServiceYearUrls(): array
    {
        $brands = self::CAR_BRANDS;
        $svcs = array_slice(self::SERVICE_TYPES, 0, 6);
        $years = range(2020, (int) date('Y') + 1);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($brands as $b) {
                foreach ($svcs as $s) {
                    foreach ($years as $y) {
                        $urls[] = "/bengkel-{$b}-{$s}-{$c}-{$y}";
                    }
                }
            }
        }
        return $urls; // 200 × 30 × 6 × ~7 = 252,000
    }

    /** /aplikasi-bengkel-{feature}-{city}-{price} */
    public static function allSourceCodeFeatureCityPriceUrls(): array
    {
        $features = ['kasir','inventory','service','invoice','customer','laporan','multi-cabang','pos','booking-online','api-mobile'];
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($features as $f) {
                foreach (array_slice(self::SC_PRICE_RANGES, 0, 6) as $p) {
                    $urls[] = "/aplikasi-bengkel-{$f}-{$c}-harga-{$p}";
                }
            }
        }
        return $urls; // 200 × 10 × 6 = 12,000
    }

    /** /paket-aplikasi-bengkel-{city}-{price} */
    public static function allSourceCodePaketCityPriceUrls(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (self::SC_PRICE_RANGES as $p) {
                $urls[] = "/paket-aplikasi-bengkel-{$c}-{$p}";
            }
        }
        return $urls; // 200 × 10 = 2,000
    }

    /** /promo-bengkel-{city}-{year} */
    public static function allPromoCityYearUrls(): array
    {
        $years = range(2020, (int) date('Y') + 1);
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($years as $y) {
                $urls[] = "/promo-bengkel-{$c}-{$y}";
            }
        }
        return $urls; // 50 × ~7 = 350
    }

    /** /bengkel-{city}-bulan-{month} */
    public static function allMonthCityUrls(): array
    {
        $months = ['januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember'];
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($months as $m) {
                $urls[] = "/bengkel-{$c}-bulan-{$m}";
            }
        }
        return $urls; // 50 × 12 = 600
    }

    /** /service-{service}-{city}-{price} */
    public static function allServiceCityPriceUrls(): array
    {
        $prices = ['50rb','100rb','150rb','200rb','300rb','500rb','1jt','2jt'];
        $svcs = array_slice(self::SERVICE_TYPES, 0, 10);
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($svcs as $s) {
                foreach ($prices as $p) {
                    $urls[] = "/{$s}-{$c}-harga-{$p}";
                }
            }
        }
        return $urls; // 50 × 10 × 8 = 4,000
    }

    // ═══════════════════════════════════════════════════
    // EXTRA MASSIVE FILLERS TO REACH 1,000,000
    // ═══════════════════════════════════════════════════

    /** SC: ALL source code keywords × ALL cities × ALL prices */
    public static function allSourceCodeKwCityPriceAll(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (self::SOURCE_CODE_KEYWORDS as $kw) {
                foreach (self::SC_PRICE_RANGES as $p) {
                    $urls[] = "/{$kw}-{$c}-harga-{$p}";
                }
            }
        }
        return $urls; // 200 × 14 × 10 = 28,000
    }

    /** SC: ALL keywords × ALL cities × ALL actions */
    public static function allSourceCodeActionCityAll(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (self::SOURCE_CODE_KEYWORDS as $kw) {
                foreach (self::SC_ACTIONS as $act) {
                    $urls[] = "/{$act}-{$kw}-{$c}";
                }
            }
        }
        return $urls; // 200 × 14 × 12 = 33,600
    }

    /** SC: ALL keywords × ALL modifiers × ALL cities */
    public static function allSourceCodeKwModCity(): array
    {
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach (self::SOURCE_CODE_KEYWORDS as $kw) {
                foreach (self::SC_MODIFIERS as $mod) {
                    $urls[] = "/{$kw}-{$mod}-{$c}";
                }
            }
        }
        return $urls; // 50 × 14 × 10 = 7,000
    }

    /** SC: keyword × city × tahun */
    public static function allSourceCodeCityYear(): array
    {
        $years = range(2015, (int) date('Y') + 2);
        $kws = array_slice(self::SOURCE_CODE_KEYWORDS, 0, 8);
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($kws as $kw) {
                foreach ($years as $y) {
                    $urls[] = "/{$kw}-{$c}-{$y}";
                }
            }
        }
        return $urls; // 50 × 8 × ~12 = 4,800
    }

    /** SC: /paket-{kw}-{city}-{year} */
    public static function allSourceCodePaketYear(): array
    {
        $years = range(2020, (int) date('Y') + 2);
        $kws = array_slice(self::SOURCE_CODE_KEYWORDS, 0, 5);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($kws as $kw) {
                foreach ($years as $y) {
                    $urls[] = "/paket-{$kw}-{$c}-{$y}";
                }
            }
        }
        return $urls; // 200 × 5 × ~8 = 8,000
    }

    /** SC: /demo-{kw}-{city} */
    public static function allSourceCodeDemoCity(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (array_slice(self::SOURCE_CODE_KEYWORDS, 0, 6) as $kw) {
                $urls[] = "/demo-{$kw}-{$c}";
            }
            // generic
            $urls[] = "/demo-aplikasi-bengkel-{$c}";
            $urls[] = "/trial-aplikasi-bengkel-{$c}";
        }
        return $urls; // 200 × 8 = 1,600
    }

    /** SC: /harga-{kw}-{city}-{year} */
    public static function allSourceCodeHargaYear(): array
    {
        $years = range(2020, (int) date('Y') + 2);
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach (array_slice(self::SOURCE_CODE_KEYWORDS, 0, 6) as $kw) {
                foreach ($years as $y) {
                    $urls[] = "/harga-{$kw}-{$c}-{$y}";
                }
            }
        }
        return $urls; // 50 × 6 × ~8 = 2,400
    }

    // ── GENERAL FILLERS ──

    /** ALL brands × ALL cities × ALL services */
    public static function allBrandCityServiceAll(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (self::CAR_BRANDS as $b) {
                foreach (array_slice(self::SERVICE_TYPES, 0, 6) as $s) {
                    $urls[] = "/bengkel-{$b}-{$s}-{$c}";
                }
            }
        }
        return $urls; // 200 × 30 × 6 = 36,000
    }

    /** ALL services × ALL cities × tahun */
    public static function allServiceCityYearAll(): array
    {
        $years = range(2018, (int) date('Y') + 1);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (self::SERVICE_TYPES as $s) {
                foreach ($years as $y) {
                    $urls[] = "/{$s}-{$c}-{$y}";
                }
            }
        }
        return $urls; // 200 × 30 × ~9 = 54,000
    }

    /** /bengkel-{brand}-{city}-{year}-{service} */
    public static function allBrandCityYearService(): array
    {
        $years = range(2020, (int) date('Y'));
        $brands = array_slice(self::CAR_BRANDS, 0, 10);
        $svcs = array_slice(self::SERVICE_TYPES, 0, 6);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($brands as $b) {
                foreach ($years as $y) {
                    foreach ($svcs as $s) {
                        $urls[] = "/bengkel-{$b}-{$c}-{$y}-{$s}";
                    }
                }
            }
        }
        return $urls; // 200 × 10 × ~5 × 6 = 60,000
    }

    /** /{service}-{brand}-{city}-{year} */
    public static function allServiceBrandCityYear(): array
    {
        $years = range(2020, (int) date('Y'));
        $brands = array_slice(self::CAR_BRANDS, 0, 10);
        $svcs = array_slice(self::SERVICE_TYPES, 0, 6);
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($svcs as $s) {
                foreach ($brands as $b) {
                    foreach ($years as $y) {
                        $urls[] = "/{$s}-{$b}-{$c}-{$y}";
                    }
                }
            }
        }
        return $urls; // 200 × 6 × 10 × ~5 = 60,000
    }

    /** /bengkel-{city}-{price}-{keyword} */
    public static function allCityPriceKeyword(): array
    {
        $prices = ['100rb','200rb','300rb','500rb','1jt','2jt','5jt','10jt'];
        $kw = ['murah','terbaik','terdekat','profesional','express','24-jam'];
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach ($prices as $p) {
                foreach ($kw as $k) {
                    $urls[] = "/bengkel-{$c}-{$p}-{$k}";
                }
            }
        }
        return $urls; // 200 × 8 × 6 = 9,600
    }

    /** /{city}-{brand}-{service} */
    public static function allCityBrandService(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (array_slice(self::CAR_BRANDS, 0, 8) as $b) {
                foreach (array_slice(self::SERVICE_TYPES, 0, 6) as $s) {
                    $urls[] = "/{$c}-{$b}-{$s}";
                }
            }
        }
        return $urls; // 200 × 8 × 6 = 9,600
    }

    /** SC: /source-code-{kw}-{city}-{price}-{year} */
    public static function allSourceCodeKwCityPriceYear(): array
    {
        $years = range(2020, (int) date('Y') + 1);
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach (array_slice(self::SOURCE_CODE_KEYWORDS, 0, 6) as $kw) {
                foreach (array_slice(self::SC_PRICE_RANGES, 0, 6) as $p) {
                    foreach ($years as $y) {
                        $urls[] = "/{$kw}-{$c}-{$p}-{$y}";
                    }
                }
            }
        }
        return $urls; // 50 × 6 × 6 × ~7 = 12,600
    }

    /** /wajib-tau-{service}-{brand}-{city} */
    public static function allInfoBrandCity(): array
    {
        $prefs = ['wajib-tau','panduan','cara','tips','rekomendasi'];
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach (array_slice(self::SERVICE_TYPES, 0, 8) as $s) {
                foreach ($prefs as $p) {
                    $urls[] = "/{$p}-{$s}";
                }
                $urls[] = "/{$s}-{$c}"; // duplicate from earlier but included for completeness
            }
        }
        // dedupe
        return array_values(array_unique($urls));
    }

    /** /bengkel-{city}-terbaik-{year}-bulan-{month} */
    public static function allCityYearMonth(): array
    {
        $months = ['januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember'];
        $years = range(2020, (int) date('Y'));
        $urls = [];
        foreach (self::CITIES_COMPACT as $c) {
            foreach ($years as $y) {
                foreach ($months as $m) {
                    $urls[] = "/bengkel-{$c}-terbaik-{$y}-{$m}";
                }
            }
        }
        return $urls; // 50 × 5 × 12 = 3,000
    }

    /** SC massive: /{kw}-{city}-{price}-{modifier} */
    public static function allSourceCodeCityPriceMod(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (array_slice(self::SOURCE_CODE_KEYWORDS, 0, 8) as $kw) {
                foreach (array_slice(self::SC_PRICE_RANGES, 0, 8) as $p) {
                    foreach (array_slice(self::SC_MODIFIERS, 0, 5) as $mod) {
                        $urls[] = "/{$kw}-{$c}-{$p}-{$mod}";
                    }
                }
            }
        }
        return $urls; // 200 × 8 × 8 × 5 = 64,000
    }

    /** SC: /jual-{kw}-{city}-{price}-{modifier} */
    public static function allSourceCodeJualCityPriceMod(): array
    {
        $urls = [];
        foreach (self::CITIES_FULL as $c) {
            foreach (array_slice(self::SOURCE_CODE_KEYWORDS, 0, 5) as $kw) {
                foreach (array_slice(self::SC_PRICE_RANGES, 0, 6) as $p) {
                    $urls[] = "/jual-{$kw}-{$c}-{$p}";
                    $urls[] = "/beli-{$kw}-{$c}-{$p}";
                    $urls[] = "/order-{$kw}-{$c}-{$p}";
                }
            }
        }
        return $urls; // 200 × 5 × 6 × 3 = 18,000
    }
}
