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
        <h1>{{ $categoryA->repair_category_name }} vs {{ $categoryB->repair_category_name }}</h1>
        <p>{{ $metaDescription }}</p>
    </header>

    <main>
        <section class="content">
            <h2>Comparison: {{ $categoryA->repair_category_name }} vs {{ $categoryB->repair_category_name }}</h2>
            <p>Choosing between {{ strtolower($categoryA->repair_category_name) }} and {{ strtolower($categoryB->repair_category_name) }} can be confusing. Both services are essential for maintaining your vehicle, but they address different needs. This comparison will help you understand which service is right for your current situation.</p>
            <p>Every vehicle has different requirements based on its age, mileage, driving conditions, and maintenance history. Understanding the difference between these service categories helps you make informed decisions about your vehicle's care.</p>

            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Criteria</th>
                        <th>{{ $categoryA->repair_category_name }}</th>
                        <th>{{ $categoryB->repair_category_name }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comparison as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['a'] }}</td>
                        <td>{{ $row['b'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="content">
            <h2>When to Choose {{ $categoryA->repair_category_name }}</h2>
            <p>{{ $categoryA->repair_category_name }} is the right choice when your vehicle shows specific symptoms related to this category. Our certified technicians at {{ config('app.name') }} can properly diagnose whether this service is what your vehicle needs.</p>
            <p>With over {{ $servicesA }} completed services in this category, {{ config('app.name') }} has the expertise to deliver quality {{ strtolower($categoryA->repair_category_name) }} results at an average price of @money($avgPriceA).</p>
        </section>

        <section class="content">
            <h2>When to Choose {{ $categoryB->repair_category_name }}</h2>
            <p>Sometimes {{ strtolower($categoryB->repair_category_name) }} is more appropriate for your vehicle's condition. Pay attention to the specific signs and symptoms that indicate this type of service is needed.</p>
            <p>We have completed {{ $servicesB }} {{ strtolower($categoryB->repair_category_name) }} services with an average cost of @money($avgPriceB).</p>
        </section>

        <section class="content">
            <h2>Which Service Is Right for You?</h2>
            <p>Ultimately, the right service depends on your vehicle's specific condition and symptoms. A professional diagnosis is the most reliable way to determine what your vehicle needs.</p>
            <p>At {{ config('app.name') }}, we begin every visit with a comprehensive inspection. Our technicians check the relevant systems and provide you with a clear explanation of what needs attention, why it matters, and how we can help.</p>
            <p>Book an appointment today and let our experts guide you to the right service for your vehicle.</p>
        </section>

        <section class="faq content">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-item">
                <h3>Which service is more expensive: {{ $categoryA->repair_category_name }} or {{ $categoryB->repair_category_name }}?</h3>
                <p>{{ $avgPriceA > $avgPriceB ? $categoryA->repair_category_name : $categoryB->repair_category_name }} tends to have a higher average price based on our service history, but every vehicle is different. Get a personalized quote at {{ config('app.name') }}.</p>
            </div>
            <div class="faq-item">
                <h3>Can I get both services done at the same time?</h3>
                <p>Yes, many customers choose to address multiple service needs during the same visit. This can often save time and may result in package discounts.</p>
            </div>
            <div class="faq-item">
                <h3>How long does each service take?</h3>
                <p>Service duration varies by complexity. Our team will give you an accurate time estimate after inspection. We also offer a comfortable waiting area and can arrange transportation if needed.</p>
            </div>
        </section>

        <section class="cta content">
            <h2>Get Expert Advice at {{ config('app.name') }}</h2>
            <p>Not sure which service your vehicle needs? Visit {{ config('app.name') }} for a professional diagnostic check and personalized recommendations.</p>
        </section>
    </main>

    <aside class="sidebar">
        <h3>More Comparisons</h3>
        <ul>
            @foreach(\App\Models\RepairCategory::whereNotIn('id', [$categoryA->id, $categoryB->id])->limit(5)->get() as $other)
            <li><a href="{{ url('/compare/' . $categoryA->slug . '-vs-' . $other->slug) }}">{{ $categoryA->repair_category_name }} vs {{ $other->repair_category_name }}</a></li>
            @endforeach
        </ul>
    </aside>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </footer>
</body>
</html>
