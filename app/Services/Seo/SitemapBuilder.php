<?php

namespace App\Services\Seo;

use App\Http\Controllers\DocsController;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Schema;

final class SitemapBuilder
{
    public const MAX_URLS_PER_SITEMAP = 50_000;

    private readonly PseoPageResolver $resolver;

    public function __construct(?PseoPageResolver $resolver = null)
    {
        $this->resolver = $resolver ?? app(PseoPageResolver::class);
    }

    /** @return list<string> */
    public function index(): array
    {
        $groups = [];
        foreach ($this->groupCounts() as $group => $count) {
            $chunks = max(1, (int) ceil($count / self::MAX_URLS_PER_SITEMAP));
            if ($chunks === 1) {
                $groups[] = $group;

                continue;
            }

            for ($chunk = 1; $chunk <= $chunks; $chunk++) {
                $groups[] = $group.'-'.$chunk;
            }
        }

        return $groups;
    }

    /** @return array<string, int> */
    private function groupCounts(): array
    {
        return [
            'pages' => count($this->pageUrls()),
            'pseo' => count($this->resolver->slugs()),
            'blogs' => count($this->blogUrls()),
        ];
    }

    /** @return list<array{loc: string, priority: string}> */
    public function urlsForGroup(string $group): array
    {
        $chunk = 1;
        if (preg_match('/^(.+)-(\d+)$/', $group, $matches)) {
            $group = $matches[1];
            $chunk = (int) $matches[2];
        }

        $urls = match ($group) {
            'pages' => $this->pageUrls(),
            'pseo' => $this->pseoUrls(),
            'blogs' => $this->blogUrls(),
            default => [],
        };

        return array_slice($urls, ($chunk - 1) * self::MAX_URLS_PER_SITEMAP, self::MAX_URLS_PER_SITEMAP);
    }

    public function totalUrlCount(): int
    {
        return array_sum($this->groupCounts());
    }

    /** @return list<array{loc: string, priority: string}> */
    private function pageUrls(): array
    {
        $base = rtrim(config('app.url'), '/');
        $urls = [
            ['loc' => $base.'/', 'priority' => '1.0'],
            ['loc' => $base.'/docs', 'priority' => '0.8'],
            ['loc' => $base.'/blog', 'priority' => '0.7'],
        ];

        foreach ((new DocsController)->sections() as $section) {
            $urls[] = ['loc' => $base.'/docs/'.$section['slug'], 'priority' => '0.6'];
        }

        return $urls;
    }

    /** @return list<array{loc: string, priority: string}> */
    private function pseoUrls(): array
    {
        $base = rtrim(config('app.url'), '/');

        return array_map(
            fn (string $slug): array => ['loc' => $base.'/'.$slug, 'priority' => $this->priority($slug)],
            $this->resolver->slugs()
        );
    }

    /** @return list<array{loc: string, priority: string}> */
    private function blogUrls(): array
    {
        $base = rtrim(config('app.url'), '/');
        $urls = [['loc' => $base.'/blog', 'priority' => '0.7']];

        if (! Schema::hasTable('blog_posts')) {
            return $urls;
        }

        foreach (BlogPost::published()->get(['slug']) as $post) {
            $urls[] = ['loc' => $base.'/blog/'.$post->slug, 'priority' => '0.6'];
        }

        if (Schema::hasTable('blog_categories')) {
            foreach (BlogCategory::query()->where('is_active', true)->get(['slug']) as $category) {
                if ($category->slug) {
                    $urls[] = ['loc' => $base.'/blog/category/'.$category->slug, 'priority' => '0.5'];
                }
            }
        }

        return $urls;
    }

    private function priority(string $slug): string
    {
        return in_array($slug, [
            'source-code-aplikasi-bengkel',
            'aplikasi-bengkel',
            'harga-aplikasi-bengkel',
            'aplikasi-bengkel-laravel',
            'aplikasi-bengkel-white-label',
        ], true) ? '1.0' : '0.7';
    }
}
