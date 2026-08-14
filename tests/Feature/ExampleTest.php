<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman welcome (marketing) harus bisa diakses guest.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\RequirePair::class,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
