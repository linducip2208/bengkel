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
        <h1>Best {{ $repairCategory->repair_category_name }} Services {{ $year }}</h1>
        <p>{{ $metaDescription }}</p>
    </header>

    <main>
        <section class="content">
            <h2>Top 10 {{ $repairCategory->repair_category_name }} Services</h2>
            <p>Our workshop has completed over {{ $topServices->count() }} {{ $repairCategory->repair_category_name }} services in {{ $year }}, helping countless vehicle owners get back on the road safely. Each service is performed by certified technicians using genuine parts and following manufacturer specifications.</p>
            <p>Whether you need routine maintenance or major {{ strtolower($repairCategory->repair_category_name) }}, our team delivers quality results at competitive prices. Below are our most notable {{ strtolower($repairCategory->repair_category_name) }} services from {{ $year }}.</p>

            @if($topServices->count() > 0)
            <div class="services-list">
                @foreach($topServices as $index => $service)
                <div class="service-card">
                    <h3>{{ $index + 1 }}. {{ $service->title }}</h3>
                    <p><strong>Vehicle:</strong> {{ $service->vehicle->number_plate ?? 'N/A' }}</p>
                    <p><strong>Customer:</strong> {{ $service->customer->name ?? 'N/A' }}</p>
                    <p><strong>Date:</strong> {{ $service->service_date->format('d M Y') }}</p>
                    <p><strong>Charge:</strong> @money($service->charge)</p>
                    @if($service->description)
                    <p>{{ $service->description }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <p>No {{ strtolower($repairCategory->repair_category_name) }} services recorded for {{ $year }} yet. Check back soon or browse our other service categories.</p>
            @endif
        </section>

        <section class="content">
            <h2>Why Choose {{ config('app.name') }} for {{ $repairCategory->repair_category_name }}?</h2>
            <p>At {{ config('app.name') }}, we pride ourselves on delivering exceptional automotive care. Our {{ strtolower($repairCategory->repair_category_name) }} services stand out because of our commitment to quality, transparency, and customer satisfaction.</p>
            <p>Our technicians undergo rigorous training and stay updated with the latest automotive technologies. We use diagnostic equipment that allows us to identify issues accurately and efficiently, reducing both repair time and costs for our customers.</p>
            <p>We believe in transparent pricing. Before any work begins, we provide a detailed estimate explaining every charge. No hidden fees, no surprises. Our customers appreciate knowing exactly what they are paying for and why each service is necessary.</p>
            <p>All our {{ strtolower($repairCategory->repair_category_name) }} services come with a warranty on parts and labor, giving you peace of mind long after you leave our workshop.</p>
        </section>

        <section class="faq content">
            <h2>Frequently Asked Questions</h2>
            @foreach($faqs as $faq)
            <div class="faq-item">
                <h3>{{ $faq['q'] }}</h3>
                <p>{{ $faq['a'] }}</p>
            </div>
            @endforeach
        </section>

        <section class="cta content">
            <h2>Book Your {{ $repairCategory->repair_category_name }} Service Today</h2>
            <p>Don't wait until a minor issue becomes a major repair. Schedule your {{ strtolower($repairCategory->repair_category_name) }} service with {{ config('app.name') }} and experience professional automotive care.</p>
            <p>Contact us or visit our workshop for a free consultation and estimate.</p>
        </section>
    </main>

    <aside class="sidebar">
        <h3>Related Services</h3>
        <ul>
            @foreach(\App\Models\RepairCategory::where('id', '!=', $repairCategory->id)->limit(5)->get() as $rel)
            <li><a href="{{ url('/best/' . $rel->slug) }}">{{ $rel->repair_category_name }}</a></li>
            @endforeach
        </ul>
    </aside>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </footer>
</body>
</html>
