<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Smoke test modul Purchase Requisition / PR (sebelumnya nol test):
 * draft -> submit -> approve + guard hapus + gate permission.
 */
class PurchaseRequisitionSmokeTest extends TestCase
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

    private function actingAsRole(string $role, array $permissions = []): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $models = [];
        foreach ($permissions as $permission) {
            $models[] = Permission::findOrCreate($permission, 'web');
        }
        if ($models) {
            $user->givePermissionTo($models);
        }
        $branch = Branch::create(['name' => 'Cabang Uji '.uniqid(), 'code' => 'T'.uniqid(), 'is_active' => true]);
        $user->branches()->sync([$branch->id]);
        $this->actingAs($user);

        return $user;
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => 'Oli Mesin',
            'product_type_id' => ProductType::create(['type' => 'T'.uniqid(), 'slug' => uniqid(), 'is_active' => true])->id,
            'unit_id' => ProductUnit::create(['name' => 'N'.uniqid(), 'abbreviation' => uniqid(), 'is_active' => true])->id,
            'price' => 100000,
            'cost_price' => 70000,
        ]);
    }

    public function test_pr_draft_submit_approve(): void
    {
        $this->actingAsRole('custom_admin', [
            'purchase-requisition.view', 'purchase-requisition.create',
            'purchase-requisition.submit', 'purchase-requisition.approve',
            'purchase-requisition.delete',
        ]);
        $product = $this->makeProduct();

        $this->post(route('purchase-requisitions.store'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ])->assertRedirect();
        $pr = PurchaseRequisition::latest('id')->firstOrFail();
        $this->assertSame('draft', $pr->status);
        $this->assertNotEmpty($pr->requisition_number);

        $this->post(route('purchase-requisitions.submit', $pr))->assertRedirect();
        $this->assertSame('submitted', $pr->fresh()->status);

        $this->post(route('purchase-requisitions.approve', $pr))->assertRedirect();
        $this->assertSame('approved', $pr->fresh()->status);

        // PR yang sudah approved tidak boleh dihapus.
        $this->delete(route('purchase-requisitions.destroy', $pr))->assertRedirect();
        $this->assertDatabaseHas('purchase_requisitions', ['id' => $pr->id]);
    }

    public function test_pr_index_requires_permission(): void
    {
        $this->actingAsRole('custom_viewer', []);

        $this->get(route('purchase-requisitions.index'))->assertForbidden();
    }
}
