<?php

namespace App\Http\Controllers;

use App\Models\RepairCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgrammaticSeoController extends Controller
{
    public function bestService(string $category, ?int $year = null): View
    {
        $repairCategory = RepairCategory::where('slug', $category)->firstOrFail();
        $year = $year ?? now()->year;

        $topServices = \App\Models\Service::where('repair_category_id', $repairCategory->id)
            ->whereYear('service_date', $year)
            ->where('done_status', 2)
            ->with(['customer', 'vehicle'])
            ->orderBy('charge', 'desc')
            ->limit(10)
            ->get();

        $faqs = $this->generateFaqs($repairCategory->repair_category_name);

        $metaTitle = "Best {$repairCategory->repair_category_name} Services {$year} | Aplikasi Bengkel Terbaik";
        $metaDescription = "Discover top {$repairCategory->repair_category_name} services for {$year} at Aplikasi Bengkel Terbaik. Expert technicians, affordable pricing, and guaranteed quality for your vehicle.";

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => "Best {$repairCategory->repair_category_name} Services {$year}",
            'description' => $metaDescription,
            'itemListElement' => $topServices->map(fn($s, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@type' => 'Service',
                    'name' => $s->title,
                    'description' => $s->description,
                    'provider' => ['@type' => 'LocalBusiness', 'name' => config('app.name')],
                    'price' => $s->charge,
                    'date' => $s->service_date->toDateString(),
                ],
            ])->toArray(),
        ];

        return view('seo.best-service', compact(
            'repairCategory', 'year', 'topServices',
            'faqs', 'metaTitle', 'metaDescription', 'jsonLd'
        ));
    }

    public function serviceAlternatives(string $slug): View
    {
        $repairCategory = RepairCategory::where('slug', $slug)->firstOrFail();

        $alternatives = RepairCategory::where('id', '!=', $repairCategory->id)
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $metaTitle = "Alternatives to {$repairCategory->repair_category_name} | Aplikasi Bengkel Terbaik";
        $metaDescription = "Looking for alternatives to {$repairCategory->repair_category_name}? Explore similar services at Aplikasi Bengkel Terbaik with expert mechanics and competitive pricing.";

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $alternatives->map(fn($alt) => [
                '@type' => 'Question',
                'name' => "Is {$alt->repair_category_name} a good alternative to {$repairCategory->repair_category_name}?",
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => "Yes, {$alt->repair_category_name} can be a suitable alternative depending on your vehicle's specific needs. Our experts can help you decide.",
                ],
            ])->toArray(),
        ];

        return view('seo.service-alternatives', compact(
            'repairCategory', 'alternatives',
            'metaTitle', 'metaDescription', 'jsonLd'
        ));
    }

    public function compareServices(string $a, string $b): View
    {
        $categoryA = RepairCategory::where('slug', $a)->firstOrFail();
        $categoryB = RepairCategory::where('slug', $b)->firstOrFail();

        $servicesA = \App\Models\Service::where('repair_category_id', $categoryA->id)->where('done_status', 2)->count();
        $servicesB = \App\Models\Service::where('repair_category_id', $categoryB->id)->where('done_status', 2)->count();

        $avgPriceA = \App\Models\Service::where('repair_category_id', $categoryA->id)->avg('charge') ?? 0;
        $avgPriceB = \App\Models\Service::where('repair_category_id', $categoryB->id)->avg('charge') ?? 0;

        $metaTitle = "{$categoryA->repair_category_name} vs {$categoryB->repair_category_name} | Aplikasi Bengkel Terbaik";
        $metaDescription = "Compare {$categoryA->repair_category_name} vs {$categoryB->repair_category_name}. See differences in cost, service count, and choose the right repair for your vehicle at Aplikasi Bengkel Terbaik.";

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $metaTitle,
            'description' => $metaDescription,
            'mainEntity' => [
                ['@type' => 'Service', 'name' => $categoryA->repair_category_name, 'provider' => ['@type' => 'LocalBusiness', 'name' => config('app.name')], 'offers' => ['@type' => 'Offer', 'price' => round($avgPriceA, 2)]],
                ['@type' => 'Service', 'name' => $categoryB->repair_category_name, 'provider' => ['@type' => 'LocalBusiness', 'name' => config('app.name')], 'offers' => ['@type' => 'Offer', 'price' => round($avgPriceB, 2)]],
            ],
        ];

        $comparison = [
            ['label' => 'Service Count', 'a' => $servicesA, 'b' => $servicesB],
            ['label' => 'Average Price', 'a' => 'Rp ' . number_format($avgPriceA, 0, ',', '.'), 'b' => 'Rp ' . number_format($avgPriceB, 0, ',', '.')],
        ];

        return view('seo.compare-services', compact(
            'categoryA', 'categoryB', 'comparison', 'servicesA', 'servicesB',
            'avgPriceA', 'avgPriceB', 'metaTitle', 'metaDescription', 'jsonLd'
        ));
    }

    public function blogArticle(string $slug): View
    {
        // Try DB first
        if (class_exists(\App\Models\BlogPost::class)) {
            $dbPost = \App\Models\BlogPost::where('slug', $slug)->published()->first();
            if ($dbPost) {
                $article = [
                    'title' => $dbPost->title,
                    'excerpt' => $dbPost->excerpt ?? '',
                    'date' => ($dbPost->published_at ?? $dbPost->created_at)->toDateString(),
                    'content' => $dbPost->content,
                ];
                $relatedCategories = RepairCategory::inRandomOrder()->limit(4)->get();

                $metaTitle = ($dbPost->meta_title ?: $dbPost->title) . ' | Aplikasi Bengkel Terbaik Blog';
                $metaDescription = $dbPost->meta_description ?: $dbPost->excerpt;

                $jsonLd = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $dbPost->title,
                    'description' => $metaDescription,
                    'author' => ['@type' => 'Organization', 'name' => config('app.name')],
                    'publisher' => ['@type' => 'Organization', 'name' => config('app.name')],
                    'datePublished' => ($dbPost->published_at ?? $dbPost->created_at)->toIso8601String(),
                ];

                return view('seo.blog-article', compact('article', 'relatedCategories', 'metaTitle', 'metaDescription', 'jsonLd'));
            }
        }

        // Fallback to static articles
        $article = $this->getStaticArticle($slug);
        $relatedCategories = RepairCategory::inRandomOrder()->limit(4)->get();

        $metaTitle = $article['title'] . ' | Aplikasi Bengkel Terbaik Blog';
        $metaDescription = $article['excerpt'];

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['title'],
            'description' => $article['excerpt'],
            'author' => ['@type' => 'Organization', 'name' => config('app.name')],
            'publisher' => ['@type' => 'Organization', 'name' => config('app.name')],
            'datePublished' => $article['date'],
        ];

        return view('seo.blog-article', compact('article', 'relatedCategories', 'metaTitle', 'metaDescription', 'jsonLd'));
    }

    protected function generateFaqs(string $categoryName): array
    {
        return [
            ['q' => "What is {$categoryName} service?", 'a' => "{$categoryName} involves comprehensive inspection, repair, and maintenance performed by certified technicians to ensure your vehicle operates at peak performance and safety standards."],
            ['q' => "How long does {$categoryName} take?", 'a' => "Depending on complexity, {$categoryName} typically takes 1-4 hours. Our team provides an accurate estimate during initial inspection."],
            ['q' => "How much does {$categoryName} cost?", 'a' => "The cost varies based on vehicle type and specific needs. We provide transparent pricing with no hidden fees - you'll receive a detailed quote before any work begins."],
            ['q' => "Do you offer warranty on {$categoryName}?", 'a' => "Yes, all services come with warranty on both parts and labor. Warranty period varies by service type and will be stated on your invoice."],
            ['q' => "Can I wait while my {$categoryName} is being done?", 'a' => "Yes, we have a comfortable waiting area with WiFi and refreshments. For longer services, we can arrange alternative transportation."],
        ];
    }

    protected function getStaticArticle(string $slug): array
    {
        $articles = [
            'car-maintenance-tips' => [
                'title' => 'Essential Car Maintenance Tips for Indonesian Roads',
                'excerpt' => 'Keep your vehicle in top condition with these proven maintenance tips tailored for Indonesian driving conditions.',
                'date' => '2025-01-15',
                'content' => '<p>Regular car maintenance is essential for ensuring your vehicle runs smoothly and safely on Indonesian roads. From the heat and humidity to stop-and-go traffic, your car faces unique challenges that require attention.</p><p>Stick to your vehicle\'s service schedule. Most manufacturers recommend service every 5,000-10,000 km or every 6 months. Skipping these can lead to bigger, costlier problems down the road.</p><p>Engine oil changes are perhaps the most critical maintenance task. In Indonesia\'s tropical climate, engine oil degrades faster due to heat and humidity. Always use the oil grade recommended by your manufacturer and change it regularly.</p><p>Your cooling system deserves extra attention. The combination of high ambient temperatures and traffic jams means your radiator works overtime. Check coolant levels monthly and flush the system per schedule.</p><p>Air conditioning maintenance is not just about comfort - it affects fuel efficiency too. A poorly maintained AC can reduce fuel economy by up to 10%. Service your AC at least once a year.</p><p>At Aplikasi Bengkel Terbaik, our certified technicians use genuine parts and follow manufacturer specifications. Book your next service appointment today.</p>',
            ],
            'signs-your-car-needs-repair' => [
                'title' => '10 Signs Your Car Needs Immediate Repair',
                'excerpt' => 'Don\'t ignore these warning signs from your vehicle. Learn when to bring your car in for professional inspection and repair.',
                'date' => '2025-02-20',
                'content' => '<p>Your car communicates with you through various signs. Recognizing these warnings early can save you from expensive repairs and dangerous situations.</p><p>Warning lights on dashboard, unusual noises, fluid leaks, vibrations during driving, reduced fuel efficiency, difficulty starting, smoke from exhaust, pulling to one side, burning smells, and poor acceleration all point to problems needing professional diagnosis.</p><p>At Aplikasi Bengkel Terbaik, our diagnostic equipment can quickly identify issues before they become major problems. Call us today or book online for a comprehensive inspection.</p>',
            ],
            'choose-right-workshop' => [
                'title' => 'How to Choose the Right Workshop for Your Vehicle',
                'excerpt' => 'Finding a trustworthy auto repair shop is crucial. Here\'s what to look for when choosing a workshop for your car.',
                'date' => '2025-03-10',
                'content' => '<p>Choosing the right workshop is one of the most important decisions a vehicle owner can make. A reliable workshop saves you money and ensures your safety and vehicle longevity.</p><p>Check certifications, read reviews, visit the facility, ask about warranties, consider specialization, and compare pricing. Quality workshops stand behind their work with clear warranty terms on parts and labor.</p><p>At Aplikasi Bengkel Terbaik, we combine certified expertise, modern equipment, genuine parts, and transparent pricing to deliver the best automotive care experience.</p>',
            ],
        ];

        if (! isset($articles[$slug])) {
            abort(404);
        }

        return $articles[$slug];
    }

    /**
     * Generic PSEO handler — menangkap SEMUA pattern URL masif
     * yang digenerate dari SeoData (1 juta+ halaman).
     * Render template generik dengan konten dinamis dari slug.
     */
    public function genericPseo(string $slug): View
    {
        $slug = ltrim($slug, '/');
        $parts = explode('-', $slug);

        // Parse keywords dari slug
        $city = null; $brand = null; $service = null; $year = null; $price = null;
        $cities = \App\Support\SeoData::cities();
        $brands = \App\Support\SeoData::brands();
        $services = \App\Support\SeoData::services();

        foreach ($parts as $p) {
            if (in_array($p, $cities)) $city = $p;
            if (in_array($p, $brands)) $brand = $p;
            if (in_array($p, $services)) $service = $p;
            if (preg_match('/^20\d\d$/', $p)) $year = $p;
            if (preg_match('/^\d+(rb|jt)$/', $p)) $price = $p;
        }

        $cityName = $city ? ucwords(str_replace('-', ' ', $city)) : null;
        $brandName = $brand ? strtoupper($brand) : null;
        $serviceName = $service ? ucwords(str_replace('-', ' ', $service)) : null;
        $priceLabel = $price ? 'Rp ' . str_replace(['rb','jt'], [' Ribu', ' Juta'], $price) : null;
        $yearLabel = $year ?? date('Y');

        // Build meta title & description
        $parts2 = [];
        if ($serviceName) $parts2[] = $serviceName;
        if ($brandName) $parts2[] = $brandName;
        if ($cityName) $parts2[] = "di {$cityName}";
        if ($priceLabel) $parts2[] = "mulai {$priceLabel}";
        if ($year > 2000) $parts2[] = "tahun {$yearLabel}";

        $context = implode(' ', $parts2);

        $isSourceCode = str_contains($slug, 'aplikasi-bengkel') || str_contains($slug, 'source-code')
            || str_contains($slug, 'beli-') || str_contains($slug, 'jual-')
            || str_contains($slug, 'download-') || str_contains($slug, 'paket-');

        if ($isSourceCode) {
            $metaTitle = "Source Code {$context} — Aplikasi Bengkel Terbaik";
            $metaDescription = "Beli source code aplikasi bengkel {$context}. Full source code Laravel, siap pakai, bisa custom. Dapatkan sekarang dengan harga terjangkau.";
        } elseif ($serviceName || $cityName) {
            $metaTitle = "{$context} — Aplikasi Bengkel Terbaik";
            $metaDescription = "Butuh {$context}? Aplikasi Bengkel Terbaik melayani {$serviceName} profesional dengan teknisi berpengalaman dan harga bersaing. Hubungi kami sekarang.";
        } else {
            $metaTitle = "Aplikasi Bengkel Terbaik — {$slug}";
            $metaDescription = "Aplikasi Bengkel Terbaik: layanan bengkel mobil profesional, service, perawatan, dan perbaikan kendaraan. Terpercaya dan berpengalaman.";
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => "Aplikasi Bengkel Terbaik " . ($cityName ? "- {$cityName}" : ''),
            'description' => $metaDescription,
            'address' => $cityName ? ['@type' => 'PostalAddress', 'addressLocality' => $cityName, 'addressCountry' => 'ID'] : null,
            'priceRange' => $priceLabel ?? 'Rp 100rb - Rp 50jt',
            'areaServed' => $cityName ?? 'Indonesia',
        ];

        $relatedServices = [];
        foreach (array_slice(\App\Support\SeoData::services(), 0, 8) as $s) {
            $relatedServices[] = ['slug' => $s, 'name' => ucwords(str_replace('-', ' ', $s))];
        }

        return view('pseo.generic', compact(
            'slug', 'cityName', 'brandName', 'serviceName', 'yearLabel', 'priceLabel',
            'context', 'parts', 'isSourceCode', 'metaTitle', 'metaDescription', 'jsonLd',
            'relatedServices'
        ));
    }

    // ==================================================================
    // MULTILINGUAL LANDING: /{lang}/bengkel-{city}[/{kelurahan}]
    // ==================================================================

    public function cityLanding(string $city): View
    {
        $cityName = ucwords(str_replace('-', ' ', $city));
        $lang = request()->segment(1);

        $t = $this->translations($lang);
        $services = \App\Support\SeoData::services();
        $brands = \App\Support\SeoData::brands();

        $metaTitle = str_replace(['{city}'], [$cityName], $t['city_title']);
        $metaDescription = str_replace(['{city}'], [$cityName], $t['city_desc']);

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => str_replace('{city}', $cityName, $t['city_biz']),
            'description' => $metaDescription,
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $cityName, 'addressCountry' => 'ID'],
            'priceRange' => 'Rp 100rb - Rp 50jt',
            'areaServed' => $cityName,
        ];

        $kelurahans = \App\Models\Kelurahan::active()
            ->where('kabupaten', 'like', "%{$cityName}%")
            ->limit(20)->get();

        return view('pseo.city-landing', compact(
            'city', 'cityName', 'lang', 't', 'services', 'brands',
            'metaTitle', 'metaDescription', 'jsonLd', 'kelurahans'
        ));
    }

    public function kelurahanLanding(string $city, string $kelurahan): View
    {
        $cityName = ucwords(str_replace('-', ' ', $city));
        $kelurahanName = ucwords(str_replace('-', ' ', $kelurahan));
        $lang = request()->segment(1);

        $t = $this->translations($lang);
        $services = \App\Support\SeoData::services();
        $brands = \App\Support\SeoData::brands();
        $kecamatan = '';

        $k = \App\Models\Kelurahan::where('slug', $kelurahan)
            ->where('kabupaten', 'like', "%{$cityName}%")
            ->first();
        if ($k) {
            $kelurahanName = $k->name;
            $kecamatan = $k->kecamatan;
        }

        $metaTitle = str_replace(['{kelurahan}','{city}'], [$kelurahanName, $cityName], $t['kel_title']);
        $metaDescription = str_replace(['{kelurahan}','{city}'], [$kelurahanName, $cityName], $t['kel_desc']);

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => str_replace(['{kelurahan}','{city}'], [$kelurahanName, $cityName], $t['kel_biz']),
            'description' => $metaDescription,
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $kelurahanName, 'addressRegion' => $cityName, 'addressCountry' => 'ID'],
        ];

        return view('pseo.kelurahan-landing', compact(
            'city', 'cityName', 'kelurahan', 'kelurahanName', 'kecamatan',
            'lang', 't', 'services', 'brands',
            'metaTitle', 'metaDescription', 'jsonLd'
        ));
    }

    public function brandCityLanding(string $brand, string $city): View
    {
        $brandName = ucwords(str_replace('-', ' ', $brand));
        $cityName = ucwords(str_replace('-', ' ', $city));
        $lang = request()->segment(1);
        $t = $this->translations($lang);

        $metaTitle = str_replace(['{brand}','{city}'], [$brandName, $cityName], $t['brand_title']);
        $metaDescription = str_replace(['{brand}','{city}'], [$brandName, $cityName], $t['brand_desc']);

        return view('pseo.generic', [
            'slug' => "bengkel-{$brand}-{$city}",
            'cityName' => $cityName,
            'brandName' => $brandName,
            'serviceName' => "Servis {$brandName}",
            'context' => "Bengkel {$brandName} {$cityName}",
            'isSourceCode' => false,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'jsonLd' => ['@context'=>'https://schema.org','@type'=>'LocalBusiness','name'=>str_replace(['{brand}','{city}'],[$brandName,$cityName],$t['brand_biz']),'address'=>['@type'=>'PostalAddress','addressLocality'=>$cityName,'addressCountry'=>'ID']],
            'relatedServices' => collect(\App\Support\SeoData::services())->map(fn($s) => ['slug'=>$s,'name'=>ucwords(str_replace('-',' ',$s))])->take(8)->toArray(),
        ]);
    }

    public function serviceCityLanding(string $service, string $city): View
    {
        $serviceName = ucwords(str_replace('-', ' ', $service));
        $cityName = ucwords(str_replace('-', ' ', $city));
        $lang = request()->segment(1);
        $t = $this->translations($lang);

        $metaTitle = str_replace(['{service}','{city}'], [$serviceName, $cityName], $t['svc_title']);
        $metaDescription = str_replace(['{service}','{city}'], [$serviceName, $cityName], $t['svc_desc']);

        return view('pseo.generic', [
            'slug' => "service-{$service}-{$city}",
            'cityName' => $cityName,
            'serviceName' => $serviceName,
            'context' => "{$serviceName} {$cityName}",
            'isSourceCode' => false,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'jsonLd' => ['@context'=>'https://schema.org','@type'=>'Service','name'=>str_replace(['{service}','{city}'],[$serviceName,$cityName],$t['svc_biz']),'provider'=>['@type'=>'LocalBusiness','name'=>config('app.name')],'areaServed'=>['@type'=>'City','name'=>$cityName]],
            'relatedServices' => collect(\App\Support\SeoData::services())->filter(fn($s) => $s !== $service)->map(fn($s) => ['slug'=>$s,'name'=>ucwords(str_replace('-',' ',$s))])->take(8)->toArray(),
        ]);
    }

    public function bestCityLanding(string $city): View
    {
        $cityName = ucwords(str_replace('-', ' ', $city));
        $lang = request()->segment(1);
        $t = $this->translations($lang);

        $metaTitle = str_replace('{city}', $cityName, $t['best_title']);
        $metaDescription = str_replace('{city}', $cityName, $t['best_desc']);

        return view('pseo.generic', [
            'slug' => "bengkel-terbaik-{$city}",
            'cityName' => $cityName,
            'context' => "Bengkel Terbaik {$cityName}",
            'isSourceCode' => false,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'jsonLd' => ['@context'=>'https://schema.org','@type'=>'ItemList','name'=>str_replace('{city}',$cityName,$t['best_biz'])],
            'relatedServices' => collect(\App\Support\SeoData::services())->map(fn($s) => ['slug'=>$s,'name'=>ucwords(str_replace('-',' ',$s))])->take(8)->toArray(),
        ]);
    }

    // ==================================================================
    // TRANSLATIONS
    // ==================================================================

    private function translations(string $lang): array
    {
        return match ($lang) {
            'en' => [
                'city_title' => 'Best Car Workshop in {city} — Professional Auto Service',
                'city_desc' => 'Looking for trusted car repair in {city}? Expert mechanics, genuine parts, transparent pricing. Servis berkala, AC, engine, brakes & more.',
                'city_biz' => 'Best Car Workshop {city}',
                'kel_title' => 'Car Workshop in {kelurahan}, {city} — Nearby Auto Repair',
                'kel_desc' => 'Need car service near {kelurahan}, {city}? We provide professional auto repair with fast turnaround, affordable pricing, and warranty on all work.',
                'kel_biz' => 'Car Workshop {kelurahan} {city}',
                'brand_title' => '{brand} Car Service in {city} — Specialist Workshop',
                'brand_desc' => '{brand} specialist workshop in {city}. Expert technicians, genuine OEM parts, routine maintenance and major repairs for all {brand} models.',
                'brand_biz' => '{brand} Specialist Workshop {city}',
                'svc_title' => '{service} in {city} — Professional Car Care',
                'svc_desc' => 'Professional {service} in {city}. Fast, reliable service with warranty. Book online or visit our workshop today.',
                'svc_biz' => '{service} {city}',
                'best_title' => 'Top 10 Best Car Workshops in {city} — Expert Reviews',
                'best_desc' => 'Discover the best car workshops in {city}. Compare prices, read reviews, and find trusted mechanics for your vehicle.',
                'best_biz' => 'Top 10 Workshops {city}',
            ],
            'de' => [
                'city_title' => 'Beste Autowerkstatt in {city} — Professioneller Auto Service',
                'city_desc' => 'Suchen Sie eine zuverlassige Autoreparatur in {city}? Erfahrene Mechaniker, Originalteile, transparente Preise.',
                'city_biz' => 'Beste Autowerkstatt {city}',
                'kel_title' => 'Autowerkstatt in {kelurahan}, {city} — Reparatur in der Nahe',
                'kel_desc' => 'Autoservice in der Nahe von {kelurahan}, {city} benotigt? Professionelle Reparatur mit schneller Abwicklung und Garantie.',
                'kel_biz' => 'Autowerkstatt {kelurahan} {city}',
                'brand_title' => '{brand} Autoservice in {city} — Spezialwerkstatt',
                'brand_desc' => '{brand} Spezialwerkstatt in {city}. Erfahrene Techniker, OEM-Originalteile fur alle {brand} Modelle.',
                'brand_biz' => '{brand} Spezialwerkstatt {city}',
                'svc_title' => '{service} in {city} — Professionelle Autopflege',
                'svc_desc' => 'Professioneller {service} in {city}. Schneller, zuverlassiger Service mit Garantie.',
                'svc_biz' => '{service} {city}',
                'best_title' => 'Top 10 Beste Autowerkstatten in {city} — Expertenbewertungen',
                'best_desc' => 'Entdecken Sie die besten Autowerkstatten in {city}. Vergleichen Sie Preise und finden Sie vertrauenswurdige Mechaniker.',
                'best_biz' => 'Top 10 Werkstatten {city}',
            ],
            default => [
                'city_title' => 'Bengkel Mobil Terbaik di {city} — Servis Profesional',
                'city_desc' => 'Cari bengkel mobil terpercaya di {city}? Teknisi berpengalaman, sparepart original, harga transparan. Servis berkala, AC, mesin, rem & lainnya.',
                'city_biz' => 'Bengkel Mobil Terbaik {city}',
                'kel_title' => 'Bengkel Mobil di {kelurahan}, {city} — Servis Terdekat',
                'kel_desc' => 'Butuh servis mobil di area {kelurahan}, {city}? Bengkel profesional dengan pengerjaan cepat, harga terjangkau, dan garansi semua pekerjaan.',
                'kel_biz' => 'Bengkel {kelurahan} {city}',
                'brand_title' => 'Bengkel {brand} {city} — Spesialis Servis {brand}',
                'brand_desc' => 'Bengkel spesialis {brand} di {city}. Teknisi ahli, sparepart original OEM, perawatan rutin dan perbaikan besar semua tipe {brand}.',
                'brand_biz' => 'Bengkel Spesialis {brand} {city}',
                'svc_title' => '{service} di {city} — Jasa Profesional',
                'svc_desc' => 'Jasa {service} profesional di {city}. Pengerjaan cepat, bergaransi. Booking online atau kunjungi bengkel kami sekarang.',
                'svc_biz' => '{service} {city}',
                'best_title' => '10 Bengkel Mobil Terbaik di {city} — Rekomendasi Ahli',
                'best_desc' => 'Temukan bengkel mobil terbaik di {city}. Bandingkan harga, baca review, dan pilih mekanik terpercaya untuk kendaraan Anda.',
                'best_biz' => '10 Bengkel Terbaik {city}',
            ],
        };
    }
}
