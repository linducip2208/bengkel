<?php

namespace App\Console\Commands;

use App\Services\Seo\IndexNowService;
use App\Services\Seo\SitemapBuilder;
use Illuminate\Console\Command;

class IndexNowSubmit extends Command
{
    protected $signature = 'seo:indexnow
                            {--all : Submit all sitemap URLs}
                            {--new : Submit only new URLs since last run}
                            {--url= : Submit a single URL}';

    protected $description = 'Submit URLs to IndexNow (Bing, Yandex, Seznam, Naver)';

    public function handle(IndexNowService $service): int
    {
        if ($this->option('url')) {
            $result = $service->submitSingle($this->option('url'));
            $this->info("Submitted 1 URL. Success: " . ($result['success'] ? 'yes' : 'no'));
            return 0;
        }

        if ($this->option('new')) {
            $builder = new SitemapBuilder;
            $urls = [];
            foreach ($builder->index() as $group) {
                foreach ($builder->urlsForGroup($group) as $u) {
                    $urls[] = $u['loc'];
                }
            }
            $result = $service->submitNewOnly($urls);
            $this->info("New URLs submitted: {$result['submitted']}");
            return 0;
        }

        $this->info('Submitting all PSEO URLs to IndexNow...');
        $result = $service->submitAll();

        if ($result['success']) {
            $this->info("Successfully submitted {$result['submitted']} URLs.");
        } else {
            $this->error('Submission failed.');
        }

        return 0;
    }
}
