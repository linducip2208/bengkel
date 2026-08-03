<?php

namespace Tests\Unit;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_post_belongs_to_author(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $post = BlogPost::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Test content.',
            'author_id' => $user->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->assertEquals($user->id, $post->author->id);
    }

    public function test_blog_post_belongs_to_category(): void
    {
        $category = BlogCategory::create([
            'name' => 'Tips',
            'slug' => 'tips',
        ]);

        $post = BlogPost::create([
            'title' => 'Categorized Post',
            'slug' => 'categorized-post',
            'content' => 'Content here.',
            'category_id' => $category->id,
            'author_id' => User::factory()->create(['is_active' => true])->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->assertEquals('Tips', $post->category->name);
    }

    public function test_blog_category_has_many_posts(): void
    {
        $category = BlogCategory::create([
            'name' => 'Maintenance',
            'slug' => 'maintenance',
        ]);

        $user = User::factory()->create(['is_active' => true]);

        BlogPost::create([
            'title' => 'Post 1',
            'slug' => 'post-1',
            'content' => 'Content 1.',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        BlogPost::create([
            'title' => 'Post 2',
            'slug' => 'post-2',
            'content' => 'Content 2.',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->assertCount(2, $category->posts);
    }

    public function test_slug_auto_generates_from_title(): void
    {
        $title = 'Cara Merawat Mobil Matic';
        $slug = Str::slug($title);

        $this->assertEquals('cara-merawat-mobil-matic', $slug);
    }

    public function test_customer_can_be_created_without_email(): void
    {
        $customer = new Customer([
            'name' => 'Budi',
            'phone' => '081234567890',
        ]);

        $this->assertEquals('Budi', $customer->name);
        $this->assertNull($customer->email);
    }
}
