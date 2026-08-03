<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const CACHE_TTL = 86400; // 24 jam

    /** Sitemap index: /sitemap.xml */
    public function index(): Response
    {
        $builder = new SitemapBuilder;
        $groups = Cache::remember('sitemap_index', self::CACHE_TTL, fn() => $builder->index());

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($groups as $group) {
            $loc = url("/sitemap-{$group}.xml");
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <lastmod>" . now()->toDateString() . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /** Individual sitemap: /sitemap-{group}.xml */
    public function show(string $group): Response
    {
        $builder = new SitemapBuilder;
        $urls = Cache::remember("sitemap_{$group}", self::CACHE_TTL, fn() => $builder->urlsForGroup($group));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $loc = htmlspecialchars($url['loc']);
            $priority = $url['priority'] ?? '0.5';
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <lastmod>" . now()->toDateString() . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>{$priority}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /** Total stats (optional debug page) */
    public function stats(): Response
    {
        $builder = new SitemapBuilder;
        $total = $builder->totalUrlCount();
        $groups = $builder->index();
        $scTotal = 0;

        // Count source code URLs
        $scGroups = ['pseo-sc-base','pseo-sc-city','pseo-sc-city-price','pseo-sc-jasa','pseo-sc-paket','pseo-sc-vs','pseo-sc-district','pseo-sc-feature','pseo-sc-full-cross','pseo-sc-kw-city-mod','pseo-sc-action-price','pseo-sc-feature-city-price','pseo-sc-paket-city-price'];
        foreach ($scGroups as $g) {
            $urls = $builder->urlsForGroup($g);
            $scTotal += count($urls);
        }

        $html = "<h1>Sitemap Stats</h1>";
        $html .= "<p>Total URLs: <strong>" . number_format($total) . "</strong></p>";
        $html .= "<p>Source Code URLs: <strong>" . number_format($scTotal) . "</strong></p>";
        $html .= "<p>Total sitemap files: <strong>" . count($groups) . "</strong></p>";
        $html .= "<p>Max URLs per sitemap: <strong>50,000</strong></p>";
        $html .= "<p>Target 1,000,000: " . ($total >= 1000000 ? '✅ TERCAPAI' : '❌ Kurang ' . number_format(1000000 - $total)) . "</p>";

        return response($html);
    }
}
