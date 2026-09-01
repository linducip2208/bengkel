<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Services\DocumentNumberService;
use App\Services\EstimateService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Concurrency-safe numbering: DocumentNumberService + UNIQUE index.
 * Never MAX(id)+1, never COUNT(*)+1.
 */
class EstimateNumberConcurrencyTest extends EstimateTestCase
{
    public function test_generates_unique_monotonic_estimate_numbers(): void
    {
        $numbers = [];

        for ($i = 0; $i < 25; $i++) {
            $numbers[] = DocumentNumberService::generate(DocumentNumberService::ESTIMATES, 'EST', 'Ym', 4);
        }

        $this->assertCount(25, array_unique($numbers));
        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^EST-\d{6}-\d{4}$/', $number);
        }
    }

    public function test_concurrent_transactions_receive_distinct_numbers(): void
    {
        // Two nested-transaction sequences interleaved like concurrent requests.
        $first = DB::transaction(function () {
            DB::transaction(function () {
                DocumentNumberService::generate(DocumentNumberService::ESTIMATES, 'EST', 'Ym', 4);
            });

            return DocumentNumberService::generate(DocumentNumberService::ESTIMATES, 'EST', 'Ym', 4);
        });
        $second = DocumentNumberService::generate(DocumentNumberService::ESTIMATES, 'EST', 'Ym', 4);

        $this->assertNotSame($first, $second);
    }

    public function test_database_rejects_duplicate_estimate_number(): void
    {
        $service = $this->makeService();

        $estimate = ServiceEstimate::create([
            'estimate_number' => 'EST-DUP-TEST-0001',
            'service_id' => $service->id,
            'status' => ServiceEstimate::STATUS_DRAFT,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        ServiceEstimate::create([
            'estimate_number' => $estimate->estimate_number,
            'service_id' => $service->id,
            'status' => ServiceEstimate::STATUS_DRAFT,
        ]);
    }

    public function test_each_created_estimate_has_a_distinct_number(): void
    {
        $service = $this->makeService();
        $second = $this->makeService();

        app(EstimateService::class)->createDraft($service, [], [$this->itemPayload()]);
        app(EstimateService::class)->createDraft($second, [], [$this->itemPayload()]);

        $numbers = ServiceEstimate::pluck('estimate_number')->all();
        $this->assertCount(2, array_unique($numbers));
    }
}
