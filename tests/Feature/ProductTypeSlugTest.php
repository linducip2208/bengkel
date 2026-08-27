<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\FuelType;
use App\Models\NotificationTemplate;
use App\Models\PaymentMethod;
use App\Models\ProductType;
use App\Models\RepairCategory;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTypeSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RequirePair::class,
            PreventRequestForgery::class,
        ]);

        $this->actingAs(User::factory()->create(['is_active' => true]));
    }

    public function test_store_suffixes_slug_when_different_names_normalize_to_the_same_slug(): void
    {
        ProductType::create([
            'type' => 'G.MAX',
            'slug' => 'gmax',
            'is_active' => true,
        ]);

        $response = $this->post(route('product-types.store'), [
            'name' => 'GMAX',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('product-types.index'))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_types', [
            'type' => 'GMAX',
            'slug' => 'gmax-2',
        ]);
    }

    public function test_update_suffixes_slug_when_renamed_type_collides_with_an_existing_slug(): void
    {
        ProductType::create([
            'type' => 'G.MAX',
            'slug' => 'gmax',
            'is_active' => true,
        ]);
        $productType = ProductType::create([
            'type' => 'Oli',
            'slug' => 'oli',
            'is_active' => true,
        ]);

        $response = $this->put(route('product-types.update', $productType), [
            'name' => 'GMAX',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('product-types.index'))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_types', [
            'id' => $productType->id,
            'type' => 'GMAX',
            'slug' => 'gmax-2',
        ]);
    }

    public function test_soft_deleted_slug_is_never_reused(): void
    {
        $deleted = ProductType::create([
            'type' => 'G.MAX',
            'slug' => 'gmax',
            'is_active' => true,
        ]);
        $deleted->delete();

        ProductType::createWithUniqueSlug([
            'type' => 'GMAX',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('product_types', [
            'type' => 'GMAX',
            'slug' => 'gmax-2',
        ]);
    }

    public function test_database_unique_index_remains_the_final_duplicate_guard(): void
    {
        ProductType::create([
            'type' => 'G.MAX',
            'slug' => 'gmax',
            'is_active' => true,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        ProductType::create([
            'type' => 'Nama lain',
            'slug' => 'gmax',
            'is_active' => true,
        ]);
    }

    public function test_all_automatically_generated_unique_slugs_are_collision_safe(): void
    {
        $cases = [
            [FuelType::class, 'fuel_type'],
            [PaymentMethod::class, 'payment'],
            [RepairCategory::class, 'repair_category_name'],
            [VehicleType::class, 'vehicle_type'],
            [BlogCategory::class, 'name'],
            [BlogPost::class, 'title'],
            [NotificationTemplate::class, 'name'],
        ];

        foreach ($cases as [$modelClass, $sourceColumn]) {
            $first = $modelClass::createWithUniqueSlug($this->slugAttributes($modelClass, $sourceColumn, 'G.MAX'));
            $second = $modelClass::createWithUniqueSlug($this->slugAttributes($modelClass, $sourceColumn, 'GMAX'));

            $this->assertSame('gmax', $first->slug, $modelClass);
            $this->assertSame('gmax-2', $second->slug, $modelClass);
        }
    }

    private function slugAttributes(string $modelClass, string $sourceColumn, string $value): array
    {
        $attributes = [$sourceColumn => $value];

        if ($modelClass === BlogPost::class) {
            $attributes += [
                'content' => 'Konten pengujian',
                'author_id' => auth()->id(),
            ];
        }

        if ($modelClass === NotificationTemplate::class) {
            $attributes += [
                'channel' => 'email',
                'subject' => 'Subjek notifikasi',
                'body' => 'Isi notifikasi',
            ];
        }

        return $attributes;
    }
}
