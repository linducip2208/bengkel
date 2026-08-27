<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateDoesNotDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_update_keeps_primary_key_and_row_count(): void
    {
        $this->withoutMiddleware([RequirePair::class, PreventRequestForgery::class]);
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $post = BlogPost::create(['title' => 'Judul Awal', 'slug' => 'judul-awal', 'content' => 'Awal']);
        $id = $post->id;
        $before = BlogPost::count();

        $this->put(route('blog.admin.update', $post), [
            'title' => 'Judul Baru',
            'content' => 'Baru',
        ])->assertRedirect(route('blog.admin.index'))->assertSessionHasNoErrors();

        $this->assertSame($before, BlogPost::count());
        $this->assertSame($id, $post->fresh()->id);
        $this->assertSame('Judul Baru', $post->fresh()->title);
    }
}
