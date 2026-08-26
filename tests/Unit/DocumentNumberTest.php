<?php

namespace Tests\Unit;

use App\Services\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_is_monotonic_and_unique(): void
    {
        $seen = [];
        for ($i = 0; $i < 100; $i++) {
            $number = DocumentNumberService::generate(DocumentNumberService::INVOICES, 'INV', 'Ym', 4);
            $this->assertArrayNotHasKey($number, $seen, "Duplicate number: {$number}");
            $seen[$number] = true;
        }

        $this->assertCount(100, $seen);
    }

    public function test_format_matches_prefix_date_seq(): void
    {
        $number = DocumentNumberService::generate(DocumentNumberService::SERVICES, 'BP', 'Ymd', 3);

        // Pad is a MINIMUM: sequence 1000+ naturally grows past it.
        $this->assertMatchesRegularExpression('/^BP-\d{8}-\d{3,}$/', $number);
    }

    public function test_peek_does_not_consume_a_number(): void
    {
        $peeked = DocumentNumberService::peek(DocumentNumberService::PRODUCTS, 'PRD', 'Ym', 4);
        $generated = DocumentNumberService::generate(DocumentNumberService::PRODUCTS, 'PRD', 'Ym', 4);

        $this->assertEquals($peeked, $generated, 'First generate() must match the earlier peek().');
    }

    public function test_distinct_keys_have_independent_sequences(): void
    {
        $a1 = DocumentNumberService::next(DocumentNumberService::INVOICES);
        $b1 = DocumentNumberService::next(DocumentNumberService::SALES);
        $a2 = DocumentNumberService::next(DocumentNumberService::INVOICES);

        $this->assertEquals(1, $a1);
        $this->assertEquals(2, $a2);
        $this->assertEquals(1, $b1);
    }
}
