<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use Tests\TestCase;

class PseoCommercialTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class]);
    }

    public function test_source_code_page_has_buyer_intent_metadata_and_cta(): void
    {
        $response = $this->get('/source-code-aplikasi-bengkel');

        $response->assertOk()
            ->assertSee('<h1>Source Code Aplikasi Bengkel Profesional</h1>', false)
            ->assertSee('Rp 7.000.000')
            ->assertSee('6281296052010')
            ->assertSee('rel="canonical" href="'.url('/source-code-aplikasi-bengkel').'"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('SoftwareApplication', false)
            ->assertSee('Full Source Code');
        $response->assertDontSee('Rp 6.000.000')->assertDontSee('081296052010');
    }

    public function test_price_and_feature_pages_are_curated_commercial_pages(): void
    {
        $this->get('/harga-aplikasi-bengkel')->assertOk()
            ->assertSee('Harga Aplikasi Bengkel &amp; Source Code', false)
            ->assertSee('Rp 7.000.000');

        $this->get('/aplikasi-bengkel-estimasi')->assertOk()
            ->assertSee('Aplikasi Bengkel dengan Estimasi Servis Customer')
            ->assertSee('print dan PDF customer-facing')
            ->assertSee('WhatsApp');
    }

    public function test_unknown_generic_slug_returns_not_found(): void
    {
        $this->get('/random-gibberish-keyword-123')->assertNotFound();
    }

    public function test_legacy_mass_slugs_return_gone_for_fast_deindex(): void
    {
        $this->get('/terdekat-ganti-oli-sorong-terbaik')->assertGone();
        $this->get('/bengkel-mahindra-tune-up-klaten')->assertGone();
        $this->get('/tune-up-bmw-cimahi-2020')->assertGone();
        $this->get('/bengkel-makassar-300rb-express')->assertGone();
        $this->get('/aplikasi-bengkel-pos-salatiga')->assertGone();
        $this->get('/erp-bengkel-indonesia-bandung-harga-10jt-lengkap')->assertGone();
    }

    public function test_gone_page_links_to_curated_commercial_pages(): void
    {
        $this->get('/tune-up-bmw-cimahi-2020')
            ->assertGone()
            ->assertSee('/aplikasi-bengkel', false)
            ->assertSee('/harga-aplikasi-bengkel', false);
    }

    public function test_sitemap_contains_curated_pseo_and_excludes_private_or_random_urls(): void
    {
        $response = $this->get('/sitemap-pseo.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('/source-code-aplikasi-bengkel')
            ->assertSee('/harga-aplikasi-bengkel')
            ->assertSee('/aplikasi-bengkel-laravel')
            ->assertSee('/aplikasi-bengkel-white-label');
        $response->assertDontSee('/random-gibberish-keyword-123')
            ->assertDontSee('/admin')
            ->assertDontSee('/customer');
    }

    public function test_home_page_positions_product_as_software_with_configured_price(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Kelola bengkel dengan sistem yang siap dikembangkan.')
            ->assertSee('Rp 7.000.000')
            ->assertSee('Konsultasi via WhatsApp')
            ->assertSee('Laravel')
            ->assertSee('MySQL');
        $response->assertDontSee('Rp 6.000.000')->assertDontSee('6281234567890');
    }
}
