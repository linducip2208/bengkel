<?php

namespace App\Http\Controllers;

use App\Services\Seo\PseoPageResolver;
use App\Services\Seo\SitemapBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const CACHE_TTL = 86400;

    /** Sitemap index: /sitemap.xml */
    public function index(): Response
    {
        $builder = new SitemapBuilder(app(PseoPageResolver::class));
        $groups = Cache::remember('sitemap_index_v2', self::CACHE_TTL, fn (): array => $builder->index());
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($groups as $group) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>'.htmlspecialchars(url('/sitemap-'.$group.'.xml'), ENT_XML1)."</loc>\n";
            $xml .= '    <lastmod>'.now()->toDateString()."</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /** Individual sitemap: /sitemap-{group}.xml */
    public function show(string $group): Response
    {
        $builder = new SitemapBuilder(app(PseoPageResolver::class));
        if (! in_array($group, $builder->index(), true)) {
            abort(404);
        }

        $urls = Cache::remember('sitemap_v2_'.$group, self::CACHE_TTL, fn (): array => $builder->urlsForGroup($group));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1)."</loc>\n";
            $xml .= '    <lastmod>'.now()->toDateString()."</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= '    <priority>'.$url['priority']."</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /** Small diagnostic page for operators; it does not define indexability. */
    public function stats(): Response
    {
        $builder = new SitemapBuilder(app(PseoPageResolver::class));
        $html = '<h1>Sitemap Stats</h1>';
        $html .= '<p>Total curated URLs: <strong>'.number_format($builder->totalUrlCount()).'</strong></p>';
        $html .= '<p>PSEO commercial URLs: <strong>'.number_format(count($builder->urlsForGroup('pseo'))).'</strong></p>';
        $html .= '<p>Sitemap files: <strong>'.count($builder->index()).'</strong></p>';
        $html .= '<p>Maximum URLs per file: <strong>50,000</strong></p>';

        return response($html);
    }
}
