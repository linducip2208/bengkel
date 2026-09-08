<?php

namespace Tests\Feature;

use App\Models\ServicePackage;
use App\Models\User;
use Spatie\Permission\Models\Role;

class EstimateServiceCatalogTest extends EstimateTestCase
{
    public function test_search_returns_active_service_name_price_and_duration(): void
    {
        $package = ServicePackage::create([
            'name' => 'GANTI KAMPAS REM',
            'price' => 75000,
            'estimated_hours' => 1,
            'description' => 'Pekerjaan penggantian kampas rem',
            'is_active' => true,
        ]);

        $this->getJson(route('estimates.catalog.services', ['q' => 'kampas']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $package->id)
            ->assertJsonPath('results.0.price', 75000)
            ->assertJsonPath('results.0.standard_minutes', 60)
            ->assertJsonPath('results.0.code', null);
    }

    public function test_inactive_services_are_excluded_and_catalog_is_paginated(): void
    {
        $inactive = ServicePackage::create(['name' => 'JASA NONAKTIF', 'is_active' => false]);
        for ($i = 0; $i < 21; $i++) {
            ServicePackage::create(['name' => 'JASA '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'price' => 10000, 'is_active' => true]);
        }

        $page = $this->getJson(route('estimates.catalog.services', ['q' => 'JASA', 'page' => 1]));
        $page->assertOk()->assertJsonCount(20, 'results');
        $ids = collect($page->json('results'))->pluck('id');
        $this->assertFalse($ids->contains($inactive->id));
        $this->assertTrue($page->json('pagination.more'));

        $page2 = $this->getJson(route('estimates.catalog.services', ['q' => 'JASA', 'page' => 2]));
        $page2->assertOk()->assertJsonCount(1, 'results');
        $this->assertFalse($page2->json('pagination.more'));
    }

    public function test_catalog_requires_estimate_permission(): void
    {
        Role::findOrCreate('service_catalog_guest', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('service_catalog_guest');
        $this->actingAs($user);

        $this->getJson(route('estimates.catalog.services'))->assertForbidden();
    }
}
