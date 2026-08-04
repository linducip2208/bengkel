<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ request()->url() }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="article:published_time" content="{{ $article['date'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
<body>
    <header>
        <h1>{{ $article['title'] }}</h1>
        <p class="article-meta">Published: {{ $article['date'] }} | By {{ config('app.name') }}</p>
    </header>

    <main>
        <article class="content">
            {!! $article['content'] !!}
        </article>

        <section class="faq content">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-item">
                <h3>How often should I service my car?</h3>
                <p>Most manufacturers recommend servicing every 5,000-10,000 km or every 6 months, whichever comes first. Following this schedule helps prevent unexpected breakdowns and extends your vehicle's lifespan.</p>
            </div>
            <div class="faq-item">
                <h3>What warranty does {{ config('app.name') }} offer?</h3>
                <p>We provide warranty on both parts and labor for all services performed at our workshop. The specific warranty period depends on the service type and will be clearly stated on your invoice.</p>
            </div>
            <div class="faq-item">
                <h3>Do you use genuine parts?</h3>
                <p>Yes, we use genuine manufacturer parts for all repairs and replacements. This ensures optimal performance, safety, and longevity for your vehicle.</p>
            </div>
        </section>

        <section class="cta content">
            <h2>Book Your Service Today</h2>
            <p>Experience professional automotive care at {{ config('app.name') }}. Our certified technicians are ready to help with any vehicle issue.</p>
            <p>Visit our workshop or book online for your next service appointment.</p>
        </section>
    </main>

    <aside class="sidebar">
        <h3>Related Articles</h3>
        <ul>
            <li><a href="{{ url('/blog/car-maintenance-tips') }}">Essential Car Maintenance Tips for Indonesian Roads</a></li>
            <li><a href="{{ url('/blog/signs-your-car-needs-repair') }}">10 Signs Your Car Needs Immediate Repair</a></li>
            <li><a href="{{ url('/blog/choose-right-workshop') }}">How to Choose the Right Workshop for Your Vehicle</a></li>
        </ul>

        <h3>Popular Services</h3>
        <ul>
            @foreach($relatedCategories as $cat)
            <li><a href="{{ url('/best/' . $cat->slug) }}">{{ $cat->repair_category_name }}</a></li>
            @endforeach
        </ul>
    </aside>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </footer>
</body>
</html>
