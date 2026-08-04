<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::with('category')->orderBy('created_at', 'desc');
        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }
        $posts = $query->paginate(15)->withQueryString();
        return view('blog.admin.index', compact('posts'));
    }

    public function create()
    {
        $categories = BlogCategory::where('is_active', true)->orderBy('name')->get();
        return view('blog.admin.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('blog_posts', 'slug')],
            'category_id' => 'nullable|exists:blog_categories,id',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        BlogPost::create([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?: Str::slug($validated['title']),
            'category_id' => $validated['category_id'] ?? null,
            'author_id' => auth()->id(),
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'featured_image' => $validated['featured_image'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'published_at' => $validated['published_at'] ?? ($request->boolean('is_published') ? now() : null),
        ]);

        return redirect()->route('blog.admin.index')->with('success', 'Artikel berhasil ditambahkan.');
    }

    public function edit(BlogPost $post)
    {
        $categories = BlogCategory::where('is_active', true)->orderBy('name')->get();
        return view('blog.admin.edit', compact('post', 'categories'));
    }

    public function update(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($post->id)],
            'category_id' => 'nullable|exists:blog_categories,id',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        $post->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?: Str::slug($validated['title']),
            'category_id' => $validated['category_id'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'featured_image' => $validated['featured_image'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'published_at' => $validated['published_at'] ?? ($request->boolean('is_published') ? now() : null),
        ]);

        return redirect()->route('blog.admin.index')->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();
        return redirect()->route('blog.admin.index')->with('success', 'Artikel berhasil dihapus.');
    }

    // --- Blog Category management ---
    public function categoryIndex()
    {
        $categories = BlogCategory::orderBy('name')->paginate(15);
        return view('blog.admin.categories', compact('categories'));
    }

    public function categoryStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('blog_categories', 'slug')],
            'description' => 'nullable|string',
        ]);

        BlogCategory::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('blog.admin.categories')->with('success', 'Kategori blog berhasil ditambahkan.');
    }

    public function categoryUpdate(Request $request, BlogCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('blog_categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('blog.admin.categories')->with('success', 'Kategori blog berhasil diperbarui.');
    }

    public function categoryDestroy(BlogCategory $category)
    {
        if ($category->posts()->exists()) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih ada artikel.');
        }
        $category->delete();
        return redirect()->route('blog.admin.categories')->with('success', 'Kategori blog berhasil dihapus.');
    }

    public function rss()
    {
        $posts = BlogPost::published()->orderBy('published_at', 'desc')->limit(20)->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
        $xml .= '<channel>';
        $xml .= '<title>Blog Aplikasi Bengkel Terbaik</title>';
        $xml .= '<link>' . config('app.url') . '/blog</link>';
        $xml .= '<description>Tips perawatan mobil, berita otomotif, dan panduan service dari Aplikasi Bengkel Terbaik.</description>';
        $xml .= '<language>id</language>';
        $xml .= '<atom:link href="' . url('/blog/feed.xml') . '" rel="self" type="application/rss+xml"/>';

        foreach ($posts as $post) {
            $xml .= '<item>';
            $xml .= '<title><![CDATA[' . $post->title . ']]></title>';
            $xml .= '<link>' . url('/blog/' . $post->slug) . '</link>';
            $xml .= '<guid>' . url('/blog/' . $post->slug) . '</guid>';
            $xml .= '<description><![CDATA[' . ($post->excerpt ?: '') . ']]></description>';
            $xml .= '<pubDate>' . $post->published_at->toRfc2822String() . '</pubDate>';
            $xml .= '</item>';
        }

        $xml .= '</channel></rss>';

        return response($xml)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
