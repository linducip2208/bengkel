<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ProductType;
use App\Models\User;
use Spatie\Permission\Models\Role;

class EstimateProductSearchTest extends EstimateTestCase
{
    public function test_search_matches_name_sku_and_barcode_and_exposes_selling_data_only(): void
    {
        $product = $this->makeProduct('KAMPAS REM AVANZA', 350000);
        $product->update(['code' => 'BRK-001', 'barcode' => '899001']);

        foreach (['KAMPAS REM AVANZA', 'BRK-001', '899001'] as $term) {
            $response = $this->getJson(route('estimates.catalog.products', ['q' => $term]));

            $response->assertOk()->assertJsonPath('results.0.id', $product->id);
            $response->assertJsonPath('results.0.selling_price', 350000);
            $response->assertJsonMissingPath('results.0.cost_price');
            $response->assertJsonMissingPath('results.0.purchase_price');
        }
    }

    public function test_only_live_products_with_active_product_types_are_returned(): void
    {
        $active = $this->makeProduct('PART AKTIF');
        $inactive = $this->makeProduct('PART NONAKTIF');
        ProductType::query()->whereKey($inactive->product_type_id)->update(['is_active' => false]);
        $deleted = $this->makeProduct('PART DIHAPUS');
        $deleted->delete();

        $ids = collect($this->getJson(route('estimates.catalog.products', ['q' => 'PART']))->json('results'))->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
        $this->assertFalse($ids->contains($deleted->id));
    }

    public function test_catalog_is_branch_scoped_and_paginated(): void
    {
        $branchA = Branch::create(['name' => 'Cabang Produk A', 'code' => 'PA'.uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'Cabang Produk B', 'code' => 'PB'.uniqid(), 'is_active' => true]);

        session(['current_branch_id' => $branchA->id]);
        $outside = $this->makeProduct('PART CABANG A');
        $outside->update(['branch_id' => $branchA->id]);

        session(['current_branch_id' => $branchB->id]);
        $inside = $this->makeProduct('PART CABANG B');
        $inside->update(['branch_id' => $branchB->id]);

        for ($i = 0; $i < 19; $i++) {
            $this->makeProduct('PART B '.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $page = $this->getJson(route('estimates.catalog.products', ['q' => 'PART', 'page' => 1]));
        $page->assertOk()->assertJsonCount(20, 'results');
        $ids = collect($page->json('results'))->pluck('id');
        $this->assertTrue($ids->contains($inside->id));
        $this->assertFalse($ids->contains($outside->id));
        $this->assertFalse($page->json('pagination.more'));

        session()->forget('current_branch_id');
    }

    public function test_catalog_requires_estimate_permission(): void
    {
        Role::findOrCreate('estimate_catalog_guest', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('estimate_catalog_guest');
        $this->actingAs($user);

        $this->getJson(route('estimates.catalog.products'))->assertForbidden();
    }
}
