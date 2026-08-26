<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    protected string $key;

    protected string $keyLocation;

    protected array $searchEngines = [
        'https://www.bing.com/indexnow',
        'https://yandex.com/indexnow',
        'https://search.seznam.cz/indexnow',
        'https://indexnow.naver.com/indexnow',
    ];

    public function __construct()
    {
        $keyPath = public_path('indexnow-key.txt');
        $this->key = file_exists($keyPath) ? trim((string) file_get_contents($keyPath)) : '';
        $this->keyLocation = config('app.url').'/indexnow-key.txt';
    }

    public function submit(array $urls): array
    {
        if (empty($urls)) {
            return ['success' => false, 'message' => 'No URLs provided'];
        }

        $payload = [
            'host' => parse_url(config('app.url'), PHP_URL_HOST),
            'key' => $this->key,
            'keyLocation' => $this->keyLocation,
            'urlList' => array_values($urls),
        ];

        $results = [];
        foreach ($this->searchEngines as $engine) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($engine, $payload);
                $results[$engine] = ['status' => $response->status(), 'success' => $response->successful()];
            } catch (\Throwable $e) {
                $results[$engine] = ['status' => 0, 'error' => $e->getMessage()];
            }
        }

        Log::info('IndexNow: submitted '.count($urls).' URLs', $results);

        return ['success' => true, 'submitted' => count($urls), 'engines' => $results];
    }

    public function submitAll(): array
    {
        $builder = new SitemapBuilder;
        $allUrls = [];
        foreach ($builder->index() as $group) {
            foreach ($builder->urlsForGroup($group) as $u) {
                $allUrls[] = $u['loc'];
            }
        }

        $chunks = array_chunk($allUrls, 5000);
        $total = 0;
        foreach ($chunks as $chunk) {
            $result = $this->submit($chunk);
            if ($result['success']) {
                $total += count($chunk);
            }
            sleep(1);
        }

        return ['success' => true, 'submitted' => $total];
    }

    public function submitSingle(string $url): array
    {
        return $this->submit([$url]);
    }

    public function submitNewOnly(array $urls): array
    {
        $lastSubmit = cache('indexnow_last_submit', []);
        $newUrls = array_values(array_diff($urls, $lastSubmit));
        if (empty($newUrls)) {
            return ['success' => true, 'submitted' => 0, 'message' => 'No new URLs'];
        }
        $result = $this->submit($newUrls);
        $allSubmitted = array_unique(array_merge($lastSubmit, $newUrls));
        if (count($allSubmitted) > 50000) {
            $allSubmitted = array_slice($allSubmitted, -50000);
        }
        cache(['indexnow_last_submit' => $allSubmitted], now()->addYear());

        return $result;
    }
}
