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
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
<body>
    <header>
        <h1>Alternatives to {{ $repairCategory->repair_category_name }}</h1>
        <p>{{ $metaDescription }}</p>
    </header>

    <main>
        <section class="content">
            <h2>Looking for Alternatives to {{ $repairCategory->repair_category_name }}?</h2>
            <p>While {{ $repairCategory->repair_category_name }} is a popular service category, you might find that another type of service better matches your vehicle's specific needs. Sometimes, what appears to be a {{ strtolower($repairCategory->repair_category_name) }} issue could be related to a different automotive system.</p>
            <p>Below are alternative service categories available at Aplikasi Bengkel Terbaik that might be relevant to your vehicle's condition:</p>

            @foreach($alternatives as $alt)
            <div class="alternative-card">
                <h3><a href="{{ url('/best/' . $alt->slug) }}">{{ $alt->repair_category_name }}</a></h3>
                <p>Explore our {{ strtolower($alt->repair_category_name) }} services to see if this might be a better match for your vehicle's needs.</p>
            </div>
            @endforeach
        </section>

        <section class="content">
            <h2>How to Choose the Right Service Category</h2>
            <p>Selecting the right service category is crucial for getting your vehicle properly repaired. Here are some tips to help you decide:</p>
            <p>First, pay attention to the symptoms your vehicle is showing. Different issues point to different systems. Strange noises when braking suggest brake service, while poor temperature control indicates AC service needs.</p>
            <p>Second, consider your vehicle's age and mileage. Older vehicles often need more comprehensive services, including engine repairs and transmission work, while newer vehicles might need routine maintenance.</p>
            <p>Third, consult with our expert technicians. They can perform a diagnostic check and recommend the most appropriate service category based on actual findings rather than guesswork.</p>
            <p>At Aplikasi Bengkel Terbaik, we always start with a thorough inspection before recommending any service. This ensures you only pay for what your vehicle actually needs.</p>
        </section>

        <section class="cta content">
            <h2>Not Sure What You Need?</h2>
            <p>Bring your vehicle to Aplikasi Bengkel Terbaik for a professional inspection. Our certified technicians will diagnose the issue and recommend the right service category for your needs.</p>
            <p>Contact us today for a free consultation.</p>
        </section>
    </main>

    <aside class="sidebar">
        <h3>Popular Services</h3>
        <ul>
            @foreach(\App\Models\RepairCategory::inRandomOrder()->limit(6)->get() as $pop)
            <li><a href="{{ url('/best/' . $pop->slug) }}">{{ $pop->repair_category_name }}</a></li>
            @endforeach
        </ul>
    </aside>

    <footer>
        <p>&copy; {{ date('Y') }} Aplikasi Bengkel Terbaik. All rights reserved.</p>
    </footer>
</body>
</html>
