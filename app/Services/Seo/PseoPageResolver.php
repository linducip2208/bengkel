<?php

namespace App\Services\Seo;

use App\Support\PseoPageData;
use App\Support\PseoPageRegistry;
use Illuminate\Support\Str;

final class PseoPageResolver
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $registry = null;

    public function resolve(string $slug): ?PseoPageData
    {
        $slug = trim($slug, '/');
        $definition = $this->definitions()[$slug] ?? null;

        if ($definition === null || ! $this->isIndexableDefinition($definition)) {
            return null;
        }

        $featureCatalog = PseoPageRegistry::features();
        $features = collect($definition['featureKeys'] ?? [])
            ->map(fn (string $key): ?array => $featureCatalog[$key] ?? null)
            ->filter()
            ->values()
            ->all();
        $price = (int) config('product.starting_price', 7_000_000);
        $priceLabel = 'Rp '.number_format($price, 0, ',', '.');
        $whatsapp = (string) config('product.whatsapp', '6281296052010');
        $metaDescription = $this->metaDescription($definition, $priceLabel, $whatsapp);
        $faq = $this->faq($definition, $priceLabel);
        $relatedLinks = $this->relatedLinks($definition['related'] ?? []);
        $canonical = url('/'.$slug);

        return new PseoPageData(
            slug: $slug,
            intent: $definition['intent'],
            title: $definition['title'],
            metaDescription: $metaDescription,
            h1: $definition['h1'],
            intro: $definition['intro'],
            eyebrow: $definition['eyebrow'],
            problem: $definition['problem'],
            solution: $definition['solution'],
            benefits: $definition['benefits'],
            features: $features,
            audience: $definition['audience'],
            faq: $faq,
            relatedLinks: $relatedLinks,
            canonical: $canonical,
            indexable: true,
            schema: $this->schema($definition, $metaDescription, $faq, $canonical, $features, $price),
            generatedAt: now()->toDateString(),
        );
    }

    /** @return array<string, PseoPageData> */
    public function indexablePages(): array
    {
        $pages = [];
        foreach (array_keys($this->definitions()) as $slug) {
            $page = $this->resolve($slug);
            if ($page?->indexable) {
                $pages[$slug] = $page;
            }
        }

        return $pages;
    }

    /** @return list<string> */
    public function slugs(): array
    {
        return array_keys($this->indexablePages());
    }

    /** @return array<string, array<string, mixed>> */
    private function definitions(): array
    {
        return $this->registry ??= PseoPageRegistry::pages();
    }

    /** @param array<string, mixed> $definition */
    private function isIndexableDefinition(array $definition): bool
    {
        $required = ['intent', 'title', 'h1', 'intro', 'problem', 'solution', 'benefits', 'featureKeys', 'audience'];

        foreach ($required as $key) {
            if (blank($definition[$key] ?? null)) {
                return false;
            }
        }

        return in_array($definition['intent'], [
            'core', 'source_code', 'price', 'technology', 'feature', 'segment', 'customization', 'comparison', 'alternative', 'use_case',
        ], true)
            && count($definition['benefits']) >= 3
            && count($definition['featureKeys']) >= 2;
    }

    /** @param array<string, mixed> $definition */
    private function metaDescription(array $definition, string $priceLabel, string $whatsapp): string
    {
        $intentLead = match ($definition['intent']) {
            'source_code' => 'Source code aplikasi bengkel berbasis Laravel untuk bengkel mobil dan motor.',
            'price' => 'Informasi harga software dan source code aplikasi bengkel profesional.',
            'technology' => 'Aplikasi bengkel web based dengan stack Laravel, PHP, dan MySQL.',
            'feature' => 'Pelajari fitur aplikasi bengkel untuk operasional workshop yang lebih teratur.',
            'comparison' => 'Bandingkan pilihan sistem untuk mengelola operasional bengkel.',
            'alternative' => 'Temukan alternatif digital untuk pencatatan dan manajemen bengkel.',
            'segment' => 'Software manajemen untuk kebutuhan bengkel dan workshop otomotif.',
            'customization' => 'Aplikasi bengkel yang dapat dibahas untuk custom workflow dan branding.',
            default => 'Aplikasi manajemen bengkel berbasis web untuk alur operasional yang terhubung.',
        };

        return Str::limit($intentLead.' '.$definition['intro'].' Harga mulai '.$priceLabel.'. WhatsApp '.$whatsapp.'.', 158, '');
    }

    /** @param array<string, mixed> $definition */
    private function faq(array $definition, string $priceLabel): array
    {
        $common = [
            ['question' => 'Apakah source code diberikan?', 'answer' => 'Source code tersedia sesuai paket dan lisensi yang disepakati. Detail ruang lingkup dapat dibahas sebelum penawaran.'],
            ['question' => 'Apakah aplikasi bisa dicustom?', 'answer' => 'Kebutuhan custom workflow, branding, integrasi, dan deployment dapat dikonsultasikan berdasarkan proses bisnis Anda.'],
            ['question' => 'Berapa harga aplikasi bengkel?', 'answer' => 'Harga mulai '.$priceLabel.'. Harga akhir menyesuaikan customisasi, branding, integrasi, deployment, dan modul tambahan.'],
            ['question' => 'Apakah cocok untuk bengkel mobil dan motor?', 'answer' => 'Ya, modul customer, kendaraan, temuan, estimasi, pekerjaan, sparepart, QC, invoice, dan laporan dapat menjadi fondasi untuk keduanya.'],
        ];

        if ($definition['intent'] === 'feature') {
            $common[] = ['question' => 'Apakah fitur ini terhubung dengan alur bengkel?', 'answer' => 'Fitur ini dijelaskan dalam konteks alur aplikasi yang menghubungkan customer masuk, pekerjaan, dokumen, dan laporan sesuai modul yang tersedia.'];
        } elseif ($definition['intent'] === 'technology') {
            $common[] = ['question' => 'Teknologi apa yang digunakan?', 'answer' => 'Stack utama yang terverifikasi di repository adalah Laravel, PHP, MySQL, dan antarmuka web berbasis browser.'];
        } elseif ($definition['intent'] === 'comparison') {
            $common[] = ['question' => 'Bagaimana memilih solusi yang tepat?', 'answer' => 'Bandingkan kebutuhan workflow, jumlah pengguna, kontrol deployment, kemampuan custom, dan rencana pengembangan sebelum memilih.'];
        }

        return $common;
    }

    /** @param list<string> $slugs */
    private function relatedLinks(array $slugs): array
    {
        $links = [];
        foreach ($slugs as $slug) {
            if (! isset($this->definitions()[$slug])) {
                continue;
            }

            $definition = $this->definitions()[$slug];
            $links[] = ['slug' => $slug, 'label' => $definition['h1']];
        }

        return $links;
    }

    /** @param array<string, mixed> $definition @param list<array<string, string>> $faq @param list<array<string, mixed>> $features */
    private function schema(array $definition, string $metaDescription, array $faq, string $canonical, array $features, int $price): array
    {
        $productName = (string) config('product.name', 'Aplikasi Bengkel Profesional');
        $featureList = array_map(fn (array $feature): string => $feature['name'], $features);

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => $canonical.'#software',
                    'name' => $productName,
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web Browser',
                    'description' => $metaDescription,
                    'featureList' => $featureList,
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => $price,
                        'priceCurrency' => 'IDR',
                        'url' => $canonical,
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $definition['h1'], 'item' => $canonical],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn (array $item): array => [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
                    ], $faq),
                ],
            ],
        ];
    }
}
