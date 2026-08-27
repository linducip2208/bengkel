<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class, PreventRequestForgery::class]);
        $this->actingAs(User::factory()->create(['is_active' => true]));
    }

    public function test_post_slug_collisions_are_normalized_and_suffixed_without_sql_errors(): void
    {
        foreach (['Service Mobil', 'service-mobil', ' Service   Mobil '] as $title) {
            $this->post(route('blog.admin.store'), [
                'title' => $title,
                'content' => 'Konten artikel',
            ])->assertRedirect(route('blog.admin.index'))->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['service-mobil', 'service-mobil-2', 'service-mobil-3'],
            BlogPost::orderBy('id')->pluck('slug')->all()
        );
    }

    public function test_post_and_category_update_keep_current_slug_and_ignore_current_id(): void
    {
        $post = BlogPost::create(['title' => 'Service Mobil', 'slug' => 'service-mobil', 'content' => 'Awal']);
        $category = BlogCategory::create(['name' => 'Tips Mobil', 'slug' => 'tips-mobil']);

        $this->put(route('blog.admin.update', $post), [
            'title' => 'Service Mobil',
            'slug' => 'service-mobil',
            'content' => 'Diperbarui',
        ])->assertSessionHasNoErrors();
        $this->put(route('blog.admin.categories.update', $category), [
            'name' => 'Tips Mobil',
        ])->assertSessionHasNoErrors();

        $this->assertSame('service-mobil', $post->fresh()->slug);
        $this->assertSame('tips-mobil', $category->fresh()->slug);
    }
}
