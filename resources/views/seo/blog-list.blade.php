@extends('pseo._layout')

@php
    $appName = config('app.name');
@endphp

@section('content')
<section class="content" style="text-align:center">
    <h2>@if(isset($activeCategory)) Kategori: {{ $activeCategory->name }} @else Blog {{ $appName }} @endif</h2>
    <p>
        @if(isset($activeCategory))
            {{ $activeCategory->description ?: "Kumpulan artikel kategori {$activeCategory->name}: tips, panduan, dan wawasan otomotif dari {$appName}." }}
        @else
            Tips perawatan mobil, berita otomotif, dan panduan service kendaraan dari {{ $appName }}.
        @endif
    </p>
</section>

<section class="content">
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.25rem">
        <a href="{{ route('blog.index') }}"
           style="padding:0.35rem 0.9rem;border-radius:999px;font-size:0.82rem;font-weight:600;text-decoration:none;border:1px solid {{ isset($activeCategory) ? '#e2e8f0' : '#2563eb' }};background:{{ isset($activeCategory) ? '#fff' : '#2563eb' }};color:{{ isset($activeCategory) ? '#334155' : '#fff' }}">
            Semua
        </a>
        @foreach(($categories ?? collect()) as $cat)
            <a href="{{ url('/blog/category/'.$cat->slug) }}"
               style="padding:0.35rem 0.9rem;border-radius:999px;font-size:0.82rem;font-weight:600;text-decoration:none;border:1px solid {{ (isset($activeCategory) && $activeCategory->id === $cat->id) ? '#2563eb' : '#e2e8f0' }};background:{{ (isset($activeCategory) && $activeCategory->id === $cat->id) ? '#2563eb' : '#fff' }};color:{{ (isset($activeCategory) && $activeCategory->id === $cat->id) ? '#fff' : '#334155' }}">
                {{ $cat->name }}
            </a>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem" class="blog-layout">
        <div>
            <h3 style="margin-top:0">Artikel Terbaru</h3>
            @if(($articles ?? collect())->isEmpty())
                <p style="color:#64748b">Belum ada artikel pada kategori ini. Lihat <a href="{{ route('blog.index') }}">semua artikel</a>.</p>
            @else
                <div style="display:grid;gap:1rem;margin-top:1rem">
                    @foreach($articles as $post)
                        <article style="border:1px solid #e2e8f0;border-radius:10px;padding:1.25rem">
                            <h3 style="margin:0 0 0.3rem">
                                <a href="{{ url('/blog/'.$post->slug) }}" style="color:#1e293b;text-decoration:none">{{ $post->title }}</a>
                            </h3>
                            <p style="color:#64748b;font-size:0.9rem;margin:0 0 0.4rem">
                                {{ optional($post->published_at ?? $post->created_at)->isoFormat('D MMM Y') }}
                                @if($post->category)
                                    &middot; <a href="{{ url('/blog/category/'.$post->category->slug) }}" style="color:#2563eb;text-decoration:none">{{ $post->category->name }}</a>
                                @endif
                            </p>
                            @if($post->excerpt)
                                <p style="margin:0;color:#475569;font-size:0.95rem">{{ \Illuminate\Support\Str::limit($post->excerpt, 160) }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        <aside>
            <div style="border:1px solid #e2e8f0;border-radius:10px;padding:1rem;margin-bottom:1rem">
                <h3 style="margin-top:0;font-size:0.95rem">Artikel Populer</h3>
                <ul style="list-style:none;padding:0;margin:0">
                    @foreach(($recent ?? ($articles ?? collect())->take(5)) as $rp)
                        <li style="margin-bottom:0.6rem;padding-left:1rem;position:relative;color:#475569;font-size:0.88rem">
                            <span style="position:absolute;left:0;color:#2563eb">&rsaquo;</span>
                            <a href="{{ url('/blog/'.$rp->slug) }}" style="color:#475569;text-decoration:none">{{ \Illuminate\Support\Str::limit($rp->title, 60) }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div style="border:1px solid #e2e8f0;border-radius:10px;padding:1rem;margin-bottom:1rem">
                <h3 style="margin-top:0;font-size:0.95rem">Kategori</h3>
                <ul style="list-style:none;padding:0;margin:0">
                    @foreach(($categories ?? collect()) as $cat)
                        <li style="margin-bottom:0.45rem;font-size:0.88rem">
                            <a href="{{ url('/blog/category/'.$cat->slug) }}" style="color:#2563eb;text-decoration:none">{{ $cat->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div style="border:1px solid #e2e8f0;border-radius:10px;padding:1rem;background:#f8fafc">
                <h3 style="margin-top:0;font-size:0.95rem">RSS Feed</h3>
                <p style="font-size:0.85rem;color:#475569;margin-bottom:0.5rem">Ikuti artikel terbaru lewat RSS reader favorit Anda.</p>
                <a href="{{ route('blog.rss') }}" style="color:#2563eb;font-size:0.88rem;font-weight:600;text-decoration:none">&#128246; {{ url('/blog/feed.xml') }}</a>
            </div>
        </aside>
    </div>
</section>

<div class="sc-cta">
    <h3>Source Code Aplikasi Bengkel</h3>
    <p>Miliki aplikasi manajemen bengkel sendiri. Full source code Laravel — bisa custom fitur sesuai kebutuhan bisnis Anda.</p>
    <a href="https://wa.me/6281234567890" class="btn">Chat WhatsApp</a>
</div>
@endsection

@push('head')
<style>
    @@media(max-width:768px) {
        .blog-layout { grid-template-columns:1fr !important }
    }
</style>
@endpush
