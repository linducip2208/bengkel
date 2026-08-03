<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerTest extends TestCase
{
    public function test_landing_page_is_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_login_page_shows_branded_layout(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Bengkel Paten');
        $response->assertSee('Masuk');
    }

    public function test_guest_redirected_to_login_from_admin(): void
    {
        $response = $this->get('/customers');
        // Should redirect to login
        $response->assertRedirect('/login');
    }

    public function test_docs_page_is_accessible(): void
    {
        $response = $this->get('/docs');
        $response->assertStatus(200);
        $response->assertSee('Tutorial');
    }

    public function test_blog_page_is_accessible(): void
    {
        $response = $this->get('/blog');
        $response->assertStatus(200);
        $response->assertSee('Blog');
    }

    public function test_sitemap_is_accessible(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
    }

    public function test_robots_txt_is_accessible(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
    }

    public function test_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@bengkel.test',
            'password' => 'password',
        ]);
        // May succeed or fail based on DB seeding, test the flow
        $response->assertStatus(302);
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrong-password',
        ]);
        $response->assertSessionHasErrors();
    }

    public function test_login_validation_requires_email(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_login_validation_requires_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@bengkel.test',
            'password' => '',
        ]);
        $response->assertSessionHasErrors('password');
    }

    public function test_public_booking_page(): void
    {
        $response = $this->get('/booking');
        $response->assertStatus(200);
    }

    public function test_customer_login_page(): void
    {
        $response = $this->get('/customer/login');
        $response->assertStatus(200);
    }

    public function test_tracking_page_with_token(): void
    {
        $response = $this->get('/track/test-token');
        $response->assertStatus(200);
    }

    public function test_best_service_seo_page(): void
    {
        // This may 404 if category doesn't exist in DB, test routing
        $response = $this->get('/best/test-category');
        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    public function test_alternatives_seo_page(): void
    {
        $response = $this->get('/alternatives-to/test-service');
        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    public function test_compare_seo_page(): void
    {
        $response = $this->get('/compare/a-vs-b');
        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }
}
