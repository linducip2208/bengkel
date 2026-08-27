<?php

namespace Tests\Feature;

use App\Imports\CustomersImport;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerImportIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_and_email_format_variants_resolve_to_same_customer(): void
    {
        $import = new CustomersImport;
        $first = $import->model(['name' => 'Budi', 'phone' => '0812-3456-7890', 'email' => ' BUDI@EXAMPLE.COM ']);
        $second = $import->model(['name' => 'Budi Baru', 'phone' => '+62 812 3456 7890', 'email' => 'budi@example.com']);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Customer::withoutGlobalScopes()->count());
        $this->assertSame('+6281234567890', $second->fresh()->phone);
        $this->assertSame('budi@example.com', $second->fresh()->email);
    }

    public function test_empty_identifiers_create_distinct_rows_and_conflicting_identifiers_never_merge_customers(): void
    {
        $import = new CustomersImport;
        $import->model(['name' => 'Tanpa ID Satu']);
        $import->model(['name' => 'Tanpa ID Dua']);
        $phoneOwner = $import->model(['name' => 'Pemilik HP', 'phone' => '081111111111']);
        $emailOwner = $import->model(['name' => 'Pemilik Email', 'email' => 'owner@example.com']);
        $before = Customer::withoutGlobalScopes()->count();

        $result = $import->model([
            'name' => 'Jangan Merge',
            'phone' => $phoneOwner->phone,
            'email' => $emailOwner->email,
        ]);

        $this->assertNull($result);
        $this->assertSame($before, Customer::withoutGlobalScopes()->count());
        $this->assertNotSame($phoneOwner->id, $emailOwner->id);
        $this->assertNotEmpty($import->errors);
    }
}
