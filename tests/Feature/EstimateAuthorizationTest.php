<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Models\User;
use App\Services\EstimateService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role & permission matrix for the estimate module.
 */
class EstimateAuthorizationTest extends EstimateTestCase
{
    private function actingAsRole(string $role, array $permissions = []): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }
        $this->actingAs($user);

        return $user;
    }

    public function test_service_advisor_can_create_update_and_send_estimates(): void
    {
        $this->actingAsRole('service_advisor', ['estimates.create', 'estimates.update', 'estimates.send', 'estimates.view']);
        $service = $this->makeService();

        $this->post("/services/{$service->id}/estimates", $this->storePayload([$this->itemPayload()]))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_service_advisor_cannot_override_or_convert(): void
    {
        $this->actingAsRole('service_advisor', ['estimates.create', 'estimates.update', 'estimates.send', 'estimates.view']);
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);
        $estimateService->markSent($estimate, 'test');

        $this->post('/estimates/'.$estimate->id.'/override-approve', ['reason' => 'wajib alasan'])
            ->assertStatus(403);

        $this->post('/estimates/'.$estimate->id.'/convert-invoice')
            ->assertStatus(403);
    }

    public function test_mechanic_cannot_create_or_modify_prices(): void
    {
        $this->actingAsRole('mekanik', []);
        $service = $this->makeService();

        $response = $this->post("/services/{$service->id}/estimates", $this->storePayload([$this->itemPayload()]));

        $response->assertStatus(403);
        $this->assertSame(0, ServiceEstimate::count());
    }

    public function test_inventory_role_is_read_only(): void
    {
        $this->actingAsRole('inventory', ['estimates.view']);
        $service = $this->makeService();
        $estimate = app(EstimateService::class)->createDraft($service, [], [$this->itemPayload()]);

        $this->get('/estimates/'.$estimate->id.'/pdf')->assertOk();
        $this->put("/estimates/{$estimate->id}", $this->storePayload([$this->itemPayload(['unit_price' => 1])]))
            ->assertStatus(403);
    }

    public function test_cashier_can_view_but_not_edit_estimates(): void
    {
        $this->actingAsRole('kasir', ['estimates.view', 'estimates.convert_invoice']);
        $service = $this->makeService();
        $estimateService = app(EstimateService::class);
        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);
        $estimateService->markSent($estimate, 'test');
        $estimateService->approve($estimate, 'public_link');

        $this->put("/estimates/{$estimate->id}", $this->storePayload([$this->itemPayload()]))
            ->assertStatus(403);

        // Cashier IS allowed to create the invoice from an approved estimate.
        $this->post('/estimates/'.$estimate->id.'/convert-invoice')->assertRedirect();
    }

    public function test_only_manager_and_above_can_override(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);
        $estimateService->markSent($estimate, 'test');

        $this->actingAsRole('manager', ['estimates.view', 'estimates.override']);
        $this->post('/estimates/'.$estimate->id.'/override-approve', ['reason' => 'Customer konfirmasi via telepon'])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $estimate->fresh()->status);
    }

    public function test_override_requires_a_reason(): void
    {
        $this->actingAsRole('manager', ['estimates.view', 'estimates.override']);
        $service = $this->makeService();
        $estimateService = app(EstimateService::class);
        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);
        $estimateService->markSent($estimate, 'test');

        $this->from('/services/'.$service->id);
        $this->post('/estimates/'.$estimate->id.'/override-approve', ['reason' => ''])
            ->assertSessionHasErrors('reason');
        $this->assertSame(ServiceEstimate::STATUS_WAITING_APPROVAL, $estimate->fresh()->status);
    }

    public function test_mechanic_sees_estimate_but_still_cannot_send(): void
    {
        $this->actingAsRole('mekanik', ['estimates.view']);
        $service = $this->makeService();
        $estimateService = app(EstimateService::class);
        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);

        $this->post('/estimates/'.$estimate->id.'/send-wa')->assertStatus(403);
    }
}
