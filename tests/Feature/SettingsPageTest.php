<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventRequestForgery;
use App\Http\Middleware\RequirePair;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman pengaturan harus render tanpa error JS fatal.
 * Regresi: duplikat `const input` dalam satu scope mematikan
 * seluruh blok script (pemilih template, builder, ingat tab).
 */
class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RequirePair::class,
            PreventRequestForgery::class,
        ]);
    }

    public function test_settings_page_renders_with_single_template_input_binding(): void
    {
        Role::findOrCreate('custom_admin', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('custom_admin');
        $user->givePermissionTo(Permission::findOrCreate('settings.view', 'web'));
        $this->actingAs($user);

        $response = $this->get(route('settings.index'));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertSame(
            1,
            substr_count($html, "const input = document.getElementById('invoiceTemplateInput')"),
            'Deklarasi const input harus tepat satu (duplikat = SyntaxError).'
        );
        $this->assertStringContainsString('settings.activeTab', $html);
    }
}
