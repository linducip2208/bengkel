<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Models\RepairCategory;
use App\Support\SeoData;

class SitemapBuilder
{
    const MAX_URLS_PER_SITEMAP = 50000;

    /** Return all sitemap group names with chunk suffixes. */
    public function index(): array
    {
        $groups = [];
        foreach ($this->patternGroups() as $group => $count) {
            $estimate = is_callable($count) ? $count() : $count;
            $chunks = max(1, (int) ceil($estimate / self::MAX_URLS_PER_SITEMAP));
            if ($chunks <= 1) {
                $groups[] = $group;
            } else {
                for ($i = 1; $i <= $chunks; $i++) {
                    $groups[] = $group . '-' . $i;
                }
            }
        }
        return $groups;
    }

    /** Pattern group definitions with URL counts. */
    protected function patternGroups(): array
    {
        return [
            'pages'                            => 12,
            'blogs'                            => 5,
            'pseo-basic'                       => 50,
            'pseo-bengkel-city'                => count(SeoData::allBengkelCityUrls()),
            'pseo-bengkel-brand-city'          => count(SeoData::allBengkelBrandCityUrls()),
            'pseo-service-city'                => count(SeoData::allServiceCityUrls()),
            'pseo-best-city'                   => count(SeoData::allBestCityUrls()),
            'pseo-best-city-year'              => count(SeoData::allBestCityYearUrls()),
            'pseo-harga-service-city'          => count(SeoData::allHargaServiceCityUrls()),
            'pseo-24-jam-city'                 => count(SeoData::all24JamCityUrls()),
            'pseo-bengkel-brand'               => count(SeoData::allBengkelBrandUrls()),
            'pseo-tips-brand'                  => count(SeoData::allTipsBrandUrls()),
            'pseo-compare-brand'               => count(SeoData::allCompareBrandUrls()),
            'pseo-alternatif-service'          => count(SeoData::allAlternatifServiceUrls()),
            'pseo-kw-city-year'                => count(SeoData::allKeywordCityYearUrls()),
            'pseo-kw-city'                     => count(SeoData::allKeywordCityUrls()),
            'pseo-brand-city-2'                => count(SeoData::allBrandCityUrls2()),
            'pseo-compare-service'             => count(SeoData::allCompareServiceUrls()),
            'pseo-brand-service-city'          => count(SeoData::allBrandServiceCityUrls()),
            'pseo-best-service-city'           => count(SeoData::allBestServiceCityUrls()),
            'pseo-short-label-city'            => count(SeoData::allShortLabelCityUrls()),
            'pseo-city-price'                  => count(SeoData::allCityPriceUrls()),
            'pseo-city-star'                   => count(SeoData::allCityStarUrls()),
            'pseo-promo-city-year'             => count(SeoData::allPromoCityYearUrls()),
            'pseo-month-city'                  => count(SeoData::allMonthCityUrls()),
            'pseo-service-city-price'           => count(SeoData::allServiceCityPriceUrls()),
            'pseo-compare-city'                => count(SeoData::allCompareCityUrls()),
            // Source Code / Jualan Aplikasi
            'pseo-sc-base'                     => count(SeoData::allSourceCodeUrls()) + count(SeoData::allSourceCodeDownloadUrls()) + count(SeoData::allSourceCodeBestUrls()),
            'pseo-sc-city'                     => count(SeoData::allSourceCodeCityUrls()),
            'pseo-sc-city-price'               => count(SeoData::allSourceCodeCityPriceUrls()),
            'pseo-sc-jasa'                     => count(SeoData::allSourceCodeJasaUrls()),
            'pseo-sc-paket'                    => count(SeoData::allSourceCodePaketUrls()),
            'pseo-sc-vs'                       => count(SeoData::allSourceCodeVsUrls()),
            'pseo-sc-district'                 => count(SeoData::allSourceCodeDistrictUrls()),
            'pseo-sc-feature'                  => count(SeoData::allSourceCodeFeatureUrls()),
            'pseo-sc-full-cross'               => count(SeoData::allSourceCodeFullCrossUrls()),
            'pseo-sc-kw-city-mod'              => count(SeoData::allSourceCodeKwCityModUrls()),
            'pseo-sc-action-price'             => count(SeoData::allSourceCodeActionPriceUrls()),
            'pseo-sc-feature-city-price'       => count(SeoData::allSourceCodeFeatureCityPriceUrls()),
            'pseo-sc-paket-city-price'         => count(SeoData::allSourceCodePaketCityPriceUrls()),
            // Massive Volume Fillers
            'pseo-massive-volume'              => count(SeoData::allMassiveVolumeUrls()),
            'pseo-brand-city-year'             => count(SeoData::allBrandCityYearUrls()),
            'pseo-super-mega-1'                => count(SeoData::allSuperMegaUrls()),
            'pseo-super-mega-2'                => count(SeoData::allSuperMegaUrls2()),
            'pseo-triple-cross'                => count(SeoData::allTripleCrossUrls()),
            'pseo-brand-city-service-year'     => count(SeoData::allBrandCityServiceYearUrls()),
            // ═══ EXTRA MASSIVE ═══
            'pseo-sc-kw-city-price-all'    => count(SeoData::allSourceCodeKwCityPriceAll()),
            'pseo-sc-action-city-all'      => count(SeoData::allSourceCodeActionCityAll()),
            'pseo-sc-kw-mod-city'          => count(SeoData::allSourceCodeKwModCity()),
            'pseo-sc-city-year'            => count(SeoData::allSourceCodeCityYear()),
            'pseo-sc-paket-year'           => count(SeoData::allSourceCodePaketYear()),
            'pseo-sc-demo-city'            => count(SeoData::allSourceCodeDemoCity()),
            'pseo-sc-harga-year'           => count(SeoData::allSourceCodeHargaYear()),
            'pseo-brand-city-service-all'  => count(SeoData::allBrandCityServiceAll()),
            'pseo-service-city-year-all'   => count(SeoData::allServiceCityYearAll()),
            'pseo-brand-city-year-service'=> count(SeoData::allBrandCityYearService()),
            'pseo-service-brand-city-year'=> count(SeoData::allServiceBrandCityYear()),
            'pseo-city-price-keyword'      => count(SeoData::allCityPriceKeyword()),
            'pseo-city-brand-service'      => count(SeoData::allCityBrandService()),
            'pseo-sc-kw-city-price-year'   => count(SeoData::allSourceCodeKwCityPriceYear()),
            'pseo-info-brand-city'         => count(SeoData::allInfoBrandCity()),
            'pseo-city-year-month'         => count(SeoData::allCityYearMonth()),
            'pseo-sc-city-price-mod'       => count(SeoData::allSourceCodeCityPriceMod()),
            'pseo-sc-jual-city-price-mod'  => count(SeoData::allSourceCodeJualCityPriceMod()),
        ];
    }

    /** Generate raw URLs for a specific group (without chunking). */
    public function urlsForGroup(string $group): array
    {
        $chunkNumber = 1;
        if (preg_match('/^(.+)-(\d+)$/', $group, $m)) {
            $group = $m[1];
            $chunkNumber = (int) $m[2];
        }

        $allUrls = $this->rawUrlsForGroup($group);

        if ($chunkNumber > 1) {
            $offset = ($chunkNumber - 1) * self::MAX_URLS_PER_SITEMAP;
            $allUrls = array_slice($allUrls, $offset, self::MAX_URLS_PER_SITEMAP);
        }

        return $allUrls;
    }

    protected function rawUrlsForGroup(string $group): array
    {
        $base = rtrim(config('app.url'), '/');

        return match ($group) {
            'pages' => $this->pageUrls($base),
            'blogs' => $this->blogUrls($base),
            'pseo-basic' => $this->basicSeoUrls($base),
            // General PSEO
            'pseo-bengkel-city'          => $this->mapUrls($base, SeoData::allBengkelCityUrls(), 0.7),
            'pseo-bengkel-brand-city'    => $this->mapUrls($base, SeoData::allBengkelBrandCityUrls(), 0.6),
            'pseo-service-city'          => $this->mapUrls($base, SeoData::allServiceCityUrls(), 0.6),
            'pseo-best-city'             => $this->mapUrls($base, SeoData::allBestCityUrls(), 0.7),
            'pseo-best-city-year'        => $this->mapUrls($base, SeoData::allBestCityYearUrls(), 0.6),
            'pseo-harga-service-city'    => $this->mapUrls($base, SeoData::allHargaServiceCityUrls(), 0.6),
            'pseo-24-jam-city'           => $this->mapUrls($base, SeoData::all24JamCityUrls(), 0.6),
            'pseo-bengkel-brand'         => $this->mapUrls($base, SeoData::allBengkelBrandUrls(), 0.7),
            'pseo-tips-brand'            => $this->mapUrls($base, SeoData::allTipsBrandUrls(), 0.5),
            'pseo-compare-brand'         => $this->mapUrls($base, SeoData::allCompareBrandUrls(), 0.4),
            'pseo-alternatif-service'    => $this->mapUrls($base, SeoData::allAlternatifServiceUrls(), 0.6),
            'pseo-kw-city-year'          => $this->mapUrls($base, SeoData::allKeywordCityYearUrls(), 0.5),
            'pseo-kw-city'               => $this->mapUrls($base, SeoData::allKeywordCityUrls(), 0.6),
            'pseo-brand-city-2'          => $this->mapUrls($base, SeoData::allBrandCityUrls2(), 0.5),
            'pseo-compare-service'       => $this->mapUrls($base, SeoData::allCompareServiceUrls(), 0.4),
            'pseo-brand-service-city'    => $this->mapUrls($base, SeoData::allBrandServiceCityUrls(), 0.5),
            'pseo-best-service-city'     => $this->mapUrls($base, SeoData::allBestServiceCityUrls(), 0.6),
            'pseo-short-label-city'      => $this->mapUrls($base, SeoData::allShortLabelCityUrls(), 0.5),
            'pseo-city-price'            => $this->mapUrls($base, SeoData::allCityPriceUrls(), 0.5),
            'pseo-city-star'             => $this->mapUrls($base, SeoData::allCityStarUrls(), 0.5),
            'pseo-promo-city-year'       => $this->mapUrls($base, SeoData::allPromoCityYearUrls(), 0.4),
            'pseo-month-city'            => $this->mapUrls($base, SeoData::allMonthCityUrls(), 0.4),
            'pseo-service-city-price'    => $this->mapUrls($base, SeoData::allServiceCityPriceUrls(), 0.5),
            'pseo-compare-city'          => $this->mapUrls($base, SeoData::allCompareCityUrls(), 0.4),
            // Source Code
            'pseo-sc-base'               => $this->mapUrls($base, array_merge(
                SeoData::allSourceCodeUrls(), SeoData::allSourceCodeDownloadUrls(), SeoData::allSourceCodeBestUrls()
            ), 0.6),
            'pseo-sc-city'               => $this->mapUrls($base, SeoData::allSourceCodeCityUrls(), 0.5),
            'pseo-sc-city-price'         => $this->mapUrls($base, SeoData::allSourceCodeCityPriceUrls(), 0.4),
            'pseo-sc-jasa'               => $this->mapUrls($base, SeoData::allSourceCodeJasaUrls(), 0.4),
            'pseo-sc-paket'              => $this->mapUrls($base, SeoData::allSourceCodePaketUrls(), 0.4),
            'pseo-sc-vs'                 => $this->mapUrls($base, SeoData::allSourceCodeVsUrls(), 0.3),
            'pseo-sc-district'           => $this->mapUrls($base, SeoData::allSourceCodeDistrictUrls(), 0.3),
            'pseo-sc-feature'            => $this->mapUrls($base, SeoData::allSourceCodeFeatureUrls(), 0.4),
            'pseo-sc-full-cross'         => $this->mapUrls($base, SeoData::allSourceCodeFullCrossUrls(), 0.3),
            'pseo-sc-kw-city-mod'        => $this->mapUrls($base, SeoData::allSourceCodeKwCityModUrls(), 0.3),
            'pseo-sc-action-price'       => $this->mapUrls($base, SeoData::allSourceCodeActionPriceUrls(), 0.3),
            'pseo-sc-feature-city-price' => $this->mapUrls($base, SeoData::allSourceCodeFeatureCityPriceUrls(), 0.3),
            'pseo-sc-paket-city-price'   => $this->mapUrls($base, SeoData::allSourceCodePaketCityPriceUrls(), 0.3),
            // Massive
            'pseo-massive-volume'        => $this->mapUrls($base, SeoData::allMassiveVolumeUrls(), 0.3),
            'pseo-brand-city-year'       => $this->mapUrls($base, SeoData::allBrandCityYearUrls(), 0.4),
            'pseo-super-mega-1'          => $this->mapUrls($base, SeoData::allSuperMegaUrls(), 0.2),
            'pseo-super-mega-2'          => $this->mapUrls($base, SeoData::allSuperMegaUrls2(), 0.2),
            'pseo-triple-cross'          => $this->mapUrls($base, SeoData::allTripleCrossUrls(), 0.2),
            'pseo-brand-city-service-year' => $this->mapUrls($base, SeoData::allBrandCityServiceYearUrls(), 0.2),
            // ═══ EXTRA MASSIVE ═══
            'pseo-sc-kw-city-price-all'    => $this->mapUrls($base, SeoData::allSourceCodeKwCityPriceAll(), 0.3),
            'pseo-sc-action-city-all'      => $this->mapUrls($base, SeoData::allSourceCodeActionCityAll(), 0.3),
            'pseo-sc-kw-mod-city'          => $this->mapUrls($base, SeoData::allSourceCodeKwModCity(), 0.3),
            'pseo-sc-city-year'            => $this->mapUrls($base, SeoData::allSourceCodeCityYear(), 0.3),
            'pseo-sc-paket-year'           => $this->mapUrls($base, SeoData::allSourceCodePaketYear(), 0.3),
            'pseo-sc-demo-city'            => $this->mapUrls($base, SeoData::allSourceCodeDemoCity(), 0.4),
            'pseo-sc-harga-year'           => $this->mapUrls($base, SeoData::allSourceCodeHargaYear(), 0.3),
            'pseo-brand-city-service-all'  => $this->mapUrls($base, SeoData::allBrandCityServiceAll(), 0.3),
            'pseo-service-city-year-all'   => $this->mapUrls($base, SeoData::allServiceCityYearAll(), 0.3),
            'pseo-brand-city-year-service' => $this->mapUrls($base, SeoData::allBrandCityYearService(), 0.2),
            'pseo-service-brand-city-year' => $this->mapUrls($base, SeoData::allServiceBrandCityYear(), 0.2),
            'pseo-city-price-keyword'      => $this->mapUrls($base, SeoData::allCityPriceKeyword(), 0.3),
            'pseo-city-brand-service'      => $this->mapUrls($base, SeoData::allCityBrandService(), 0.3),
            'pseo-sc-kw-city-price-year'   => $this->mapUrls($base, SeoData::allSourceCodeKwCityPriceYear(), 0.2),
            'pseo-info-brand-city'         => $this->mapUrls($base, SeoData::allInfoBrandCity(), 0.3),
            'pseo-city-year-month'         => $this->mapUrls($base, SeoData::allCityYearMonth(), 0.2),
            'pseo-sc-city-price-mod'       => $this->mapUrls($base, SeoData::allSourceCodeCityPriceMod(), 0.2),
            'pseo-sc-jual-city-price-mod'  => $this->mapUrls($base, SeoData::allSourceCodeJualCityPriceMod(), 0.2),
            default => [],
        };
    }

    public function totalUrlCount(): int
    {
        $sum = 0;
        foreach ($this->patternGroups() as $group => $count) {
            $sum += is_callable($count) ? $count() : $count;
        }
        return $sum;
    }

    // ═══ Helpers ═══

    private function mapUrls(string $base, array $paths, float $priority = 0.6): array
    {
        return array_map(fn($p) => [
            'loc' => $base . $p,
            'priority' => number_format($priority, 1),
        ], $paths);
    }

    private function pageUrls(string $base): array
    {
        return [
            ['loc' => "$base/",              'priority' => '1.0'],
            ['loc' => "$base/docs",          'priority' => '0.8'],
            ['loc' => "$base/track/test",    'priority' => '0.3'],
            ['loc' => "$base/sitemap.xml",   'priority' => '0.1'],
        ];
    }

    private function blogUrls(string $base): array
    {
        $urls = [];
        if (class_exists(BlogPost::class)) {
            $posts = BlogPost::where('is_published', true)
                ->where('published_at', '<=', now())
                ->get(['slug']);
            foreach ($posts as $post) {
                $urls[] = ['loc' => "$base/blog/{$post->slug}", 'priority' => '0.7'];
            }
        }
        $urls[] = ['loc' => "$base/blog", 'priority' => '0.8'];
        return $urls;
    }

    private function basicSeoUrls(string $base): array
    {
        $urls = [];
        $categories = RepairCategory::all();
        foreach ($categories as $cat) {
            if ($cat->slug) {
                $urls[] = ['loc' => "$base/best/{$cat->slug}", 'priority' => '0.7'];
                $urls[] = ['loc' => "$base/best/{$cat->slug}/".date('Y'), 'priority' => '0.7'];
                $urls[] = ['loc' => "$base/alternatives-to/{$cat->slug}", 'priority' => '0.6'];
            }
        }
        foreach ($categories as $a) {
            foreach ($categories as $b) {
                if ($a->id < $b->id && $a->slug && $b->slug) {
                    $urls[] = ['loc' => "$base/compare/{$a->slug}-vs-{$b->slug}", 'priority' => '0.5'];
                }
            }
        }
        // Blog articles
        $slugs = ['car-maintenance-tips', 'signs-your-car-needs-repair', 'choose-right-workshop'];
        foreach ($slugs as $slug) {
            $urls[] = ['loc' => "$base/blog/$slug", 'priority' => '0.6'];
        }
        return $urls;
    }
}
