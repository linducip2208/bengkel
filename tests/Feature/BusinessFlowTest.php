<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_blog_post(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = BlogPost::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Test content here.',
            'is_published' => true,
            'published_at' => now(),
            'author_id' => $user->id,
        ]);

        $this->assertDatabaseHas('blog_posts', ['slug' => 'test-post']);
    }

    public function test_can_create_customer(): void
    {
        // Test that customer can be created without email
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '08123456789',
        ]);

        $this->assertNull($customer->email);
        $this->assertEquals('Test Customer', $customer->name);
    }

    public function test_customer_email_is_optional(): void
    {
        $customer = Customer::create([
            'name' => 'No Email Customer',
        ]);

        $this->assertNotNull($customer);
        $this->assertNull($customer->email);
    }

    public function test_blog_post_can_be_published(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $post = BlogPost::create([
            'title' => 'Published Post',
            'slug' => 'published-post',
            'content' => 'Published content.',
            'is_published' => true,
            'published_at' => now(),
            'author_id' => $user->id,
        ]);

        $published = BlogPost::published()->get();
        $this->assertCount(1, $published);
    }

    public function test_blog_post_not_visible_when_draft(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        BlogPost::create([
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'content' => 'Draft content.',
            'is_published' => false,
            'author_id' => $user->id,
        ]);

        $published = BlogPost::published()->get();
        $this->assertCount(0, $published);
    }
}
