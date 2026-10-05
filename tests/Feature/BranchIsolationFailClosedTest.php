<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FuelType;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\RepairCategory;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Support\BranchAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FAIL-CLOSED branch isolation regression matrix.
 *
 * Authorization states:
 *  - unrestricted (super_admin/admin): sees all branches incl. NULL
 *  - restricted + ids: sees ONLY assigned branches (never NULL)
 *  - restricted + zero ids (denied): sees NOTHING (zero rows)
 */
class BranchIsolationFailClosedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class]);
    }

    // ---------- helpers ----------

    private function makeBranch(string $code): Branch
    {
        return Branch::create(['name' => 'Branch '.$code, 'code' => $code, 'is_active' => true]);
    }

    private function makeUser(string $role, array $branchIds = []): User
    {
        // Reset auth guards so Spatie resolves the `web` guard when assigning.
        app('auth')->forgetGuards();
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findByName($role, 'web'));
        if ($branchIds !== []) {
            $user->branches()->sync($branchIds);
        }
        app('auth')->forgetGuards();

        return $user;
    }

    private function actAs(User $user): User
    {
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    private function seedFixtures(Branch $a, Branch $b, Branch $c, User $sys): array
    {
        $method = PaymentMethod::create(['payment' => 'Cash', 'slug' => 'cash-'.$a->id.uniqid(), 'is_active' => true]);

        $mkCustomer = fn (Branch $br, string $suffix) => Customer::withoutGlobalScopes()->create([
            'name' => "Cust {$suffix}", 'phone' => '08'.mt_rand(100000000, 199999999).$suffix, 'branch_id' => $br->id,
        ]);

        $custA = $mkCustomer($a, 'A');
        $custB = $mkCustomer($b, 'B');
        $custC = $mkCustomer($c, 'C');

        $mkIncome = fn (Branch $br, Customer $cust, string $no) => Income::withoutGlobalScopes()->create([
            'invoice_number' => $no, 'customer_id' => $cust->id, 'payment_method_id' => $method->id,
            'amount' => 50000, 'income_date' => now()->toDateString(), 'label' => 'L',
            'created_by' => $sys->id, 'branch_id' => $br->id,
        ]);

        $incomeA = $mkIncome($a, $custA, 'INV-A-'.$a->id);
        $incomeB = $mkIncome($b, $custB, 'INV-B-'.$b->id);

        // NULL-branch (global/legacy) rows: unrestricted-only.
        $custNull = Customer::withoutGlobalScopes()->create(['name' => 'Cust NULL', 'phone' => '08'.mt_rand(200000000, 299999999), 'branch_id' => null]);
        $incomeNull = Income::withoutGlobalScopes()->create([
            'invoice_number' => 'INV-NULL-'.$a->id, 'customer_id' => $custNull->id, 'payment_method_id' => $method->id,
            'amount' => 1000, 'income_date' => now()->toDateString(), 'label' => 'null',
            'created_by' => $sys->id, 'branch_id' => null,
        ]);

        // Invoice chain for branch A and B (for show/pdf tests).
        $invA = $this->makeInvoice($a, $custA, $sys);
        $invB = $this->makeInvoice($b, $custB, $sys);

        return compact('custA', 'custB', 'custC', 'incomeA', 'incomeB', 'custNull', 'incomeNull', 'invA', 'invB', 'method');
    }

    private function makeInvoice(Branch $branch, Customer $customer, User $sys): Invoice
    {
        $type = VehicleType::create(['vehicle_type' => 'T', 'slug' => uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'B'.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'F', 'slug' => uniqid()]);
        $vehicle = Vehicle::withoutGlobalScopes()->create([
            'customer_id' => $customer->id, 'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id, 'fuel_type_id' => $fuel->id,
            'number_plate' => 'B '.uniqid(), 'model_name' => 'M', 'branch_id' => $branch->id,
        ]);
        $cat = RepairCategory::create(['repair_category_name' => 'G'.uniqid(), 'slug' => uniqid(), 'is_active' => true]);
        $service = Service::withoutGlobalScopes()->create([
            'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'repair_category_id' => $cat->id, 'service_date' => now()->toDateString(),
            'title' => 'S', 'branch_id' => $branch->id, 'created_by' => $sys->id,
        ]);

        return Invoice::withoutGlobalScopes()->create([
            'invoice_number' => 'INV-'.uniqid(), 'customer_id' => $customer->id,
            'service_id' => $service->id, 'invoice_date' => now()->toDateString(),
            'total_amount' => 100, 'grand_total' => 100, 'branch_id' => $branch->id,
            'created_by' => $sys->id,
        ]);
    }

    // ---------- A. unrestricted admin ----------

    public function test_a_unrestricted_admin_reads_all_branches(): void
    {
        $a = $this->makeBranch('FA1');
        $b = $this->makeBranch('FB1');
        $c = $this->makeBranch('FC1');
        $sys = $this->makeUser('super_admin');
        $f = $this->seedFixtures($a, $b, $c, $sys);

        $admin = $this->makeUser('admin');
        $this->actAs($admin);

        $this->assertTrue($admin->branchAccess()->isUnrestricted());
        $this->assertNotNull(Customer::whereKey($f['custA']->id)->first());
        $this->assertNotNull(Customer::whereKey($f['custB']->id)->first());
        // NULL-branch visible to unrestricted (existing intended behaviour).
        $this->assertNotNull(Customer::whereKey($f['custNull']->id)->first());

        $res = $this->getJson('/api/v1/customers');
        $res->assertOk();
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($f['custA']->id, $ids);
        $this->assertContains($f['custB']->id, $ids);
    }

    // ---------- B. restricted single branch ----------

    public function test_b_restricted_single_branch_isolation(): void
    {
        $a = $this->makeBranch('FB2');
        $b = $this->makeBranch('FC2');
        $c = $this->makeBranch('FD2');
        $sys = $this->makeUser('super_admin');
        $f = $this->seedFixtures($a, $b, $c, $sys);

        $user = $this->makeUser('mekanik', [$a->id]);
        $this->actAs($user);

        $access = $user->branchAccess();
        $this->assertFalse($access->isUnrestricted());
        $this->assertEquals([$a->id], $access->branchIds);

        // Model layer.
        $this->assertNotNull(Customer::whereKey($f['custA']->id)->first());
        $this->assertNull(Customer::whereKey($f['custB']->id)->first());
        $this->assertNull(Customer::whereKey($f['custNull']->id)->first(), 'Restricted must not see NULL-branch rows');
        $names = Customer::pluck('id')->all();
        $this->assertContains($f['custA']->id, $names);
        $this->assertNotContains($f['custB']->id, $names);

        // API layer: index only A, show B => 404.
        $res = $this->getJson('/api/v1/customers');
        $res->assertOk();
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($f['custA']->id, $ids);
        $this->assertNotContains($f['custB']->id, $ids);

        $this->getJson('/api/v1/customers/'.$f['custA']->id)->assertOk();
        $this->getJson('/api/v1/customers/'.$f['custB']->id)->assertNotFound();

        $this->getJson('/api/v1/invoices/'.$f['invA']->id)->assertOk();
        $this->getJson('/api/v1/invoices/'.$f['invB']->id)->assertNotFound();
        $this->getJson('/api/v1/invoices/'.$f['invB']->id.'/pdf')->assertNotFound();
    }

    // ---------- C. restricted multi branch ----------

    public function test_c_restricted_multi_branch(): void
    {
        $a = $this->makeBranch('MC1');
        $b = $this->makeBranch('MC2');
        $c = $this->makeBranch('MC3');
        $sys = $this->makeUser('super_admin');
        $f = $this->seedFixtures($a, $b, $c, $sys);

        $user = $this->makeUser('mekanik', [$a->id, $b->id]);
        $this->actAs($user);

        $this->assertNotNull(Customer::whereKey($f['custA']->id)->first());
        $this->assertNotNull(Customer::whereKey($f['custB']->id)->first());
        $this->assertNull(Customer::whereKey($f['custC']->id)->first());
        $this->assertNull(Customer::whereKey($f['custNull']->id)->first());
    }

    // ---------- D. zero branches denied ----------

    public function test_d_zero_assignment_sees_nothing(): void
    {
        $a = $this->makeBranch('ZD1');
        $b = $this->makeBranch('ZD2');
        $c = $this->makeBranch('ZD3');
        $sys = $this->makeUser('super_admin');
        $f = $this->seedFixtures($a, $b, $c, $sys);

        $user = $this->makeUser('kasir'); // no branches
        $this->actAs($user);

        $this->assertTrue($user->branchAccess()->isDenied());
        $this->assertFalse($user->hasBranchAccess($a->id));
        $this->assertFalse($user->hasBranchAccess(null));

        $this->assertSame(0, Customer::count());
        $this->assertNull(Customer::whereKey($f['custA']->id)->first());
        $this->assertNull(Customer::whereKey($f['custNull']->id)->first());
        $this->assertSame(0, Income::count());
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Expense::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(0, PurchaseOrder::count());

        $this->getJson('/api/v1/customers')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/customers/'.$f['custA']->id)->assertNotFound();
        $this->getJson('/api/v1/invoices')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/invoices/'.$f['invA']->id)->assertNotFound();
        $this->getJson('/api/v1/invoices/'.$f['invA']->id.'/pdf')->assertNotFound();
    }

    // ---------- E. write path ----------

    public function test_e_write_path_fail_closed(): void
    {
        $a = $this->makeBranch('WE1');
        $b = $this->makeBranch('WE2');
        Role::findOrCreate('kasir', 'web');

        // Zero-branch user cannot create.
        $zero = $this->makeUser('kasir');
        $this->actAs($zero);
        $this->expectException(AuthorizationException::class);
        Customer::create(['name' => 'Nope', 'phone' => '0810000001']);
    }

    public function test_e_single_branch_auto_assigns(): void
    {
        $a = $this->makeBranch('WE3');
        $this->makeBranch('WE4');
        $user = $this->makeUser('kasir', [$a->id]);
        $this->actAs($user);

        $c = Customer::create(['name' => 'Auto', 'phone' => '0810000002']);
        $this->assertEquals($a->id, $c->branch_id);
    }

    public function test_e_multi_branch_requires_explicit_context(): void
    {
        $a = $this->makeBranch('WE5');
        $b = $this->makeBranch('WE6');
        $user = $this->makeUser('kasir', [$a->id, $b->id]);
        $this->actAs($user);

        try {
            Customer::create(['name' => 'Guess', 'phone' => '0810000003']);
            $this->fail('Multi-branch create without context must fail closed.');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }

        // Explicit authorized branch works.
        $c = Customer::create(['name' => 'Explicit', 'phone' => '0810000004', 'branch_id' => $b->id]);
        $this->assertEquals($b->id, $c->branch_id);

        // Explicit unauthorized branch fails.
        $other = $this->makeBranch('WE7');
        try {
            Customer::create(['name' => 'Escape', 'phone' => '0810000005', 'branch_id' => $other->id]);
            $this->fail('Cross-branch create must fail.');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_e_cannot_update_or_delete_other_branch(): void
    {
        $a = $this->makeBranch('WU1');
        $b = $this->makeBranch('WU2');
        $sys = $this->makeUser('super_admin');
        $custB = Customer::withoutGlobalScopes()->create(['name' => 'B', 'phone' => '0810000011', 'branch_id' => $b->id]);

        $user = $this->makeUser('kasir', [$a->id]);
        $this->actAs($user);

        // Cannot even read it, so update via scoped query is impossible...
        $this->assertNull(Customer::whereKey($custB->id)->first());

        // ...and direct branch move is rejected.
        $mine = Customer::create(['name' => 'Mine', 'phone' => '0810000012']);
        try {
            $mine->update(['branch_id' => $b->id]);
            $this->fail('Moving row to another branch must fail.');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }

        // Delete of other branch via scoped lookup fails (404-style null).
        $this->assertNull(Customer::whereKey($custB->id)->first());
        $this->assertNotNull(Customer::withoutGlobalScopes()->find($custB->id));
    }

    // ---------- F. NULL branch policy ----------

    public function test_f_null_branch_policy(): void
    {
        $a = $this->makeBranch('NB1');
        $sys = $this->makeUser('super_admin');
        $nullCust = Customer::withoutGlobalScopes()->create(['name' => 'Null', 'phone' => '0810000021', 'branch_id' => null]);

        // Unrestricted sees NULL.
        $admin = $this->makeUser('admin');
        $this->actAs($admin);
        $this->assertNotNull(Customer::whereKey($nullCust->id)->first());

        // Restricted with assignment does NOT see NULL.
        $user = $this->makeUser('mekanik', [$a->id]);
        $this->actAs($user);
        $this->assertNull(Customer::whereKey($nullCust->id)->first());

        // Zero-assignment does NOT see NULL.
        $zero = $this->makeUser('kasir');
        $this->actAs($zero);
        $this->assertNull(Customer::whereKey($nullCust->id)->first());
    }

    // ---------- G. role vs branch independence ----------

    public function test_g_role_middleware_intact_and_branch_independent(): void
    {
        $a = $this->makeBranch('RL1');
        $b = $this->makeBranch('RL2');
        $sys = $this->makeUser('super_admin');
        $f = $this->seedFixtures($a, $b, $a, $sys);

        // kasir lacks manager role: write to /invoices must 403 even for own branch.
        $kasir = $this->makeUser('kasir', [$a->id]);
        $this->actAs($kasir);
        $this->postJson('/api/v1/invoices', ['service_id' => 9999, 'invoice_date' => now()->toDateString()])
            ->assertStatus(403);

        // Branch isolation works regardless of role passing: admin of branch A
        // still cannot read branch B (use manager role w/ branch A).
        $mgr = $this->makeUser('manager', [$a->id]);
        $this->actAs($mgr);
        $this->getJson('/api/v1/customers/'.$f['custB']->id)->assertNotFound();
    }

    // ---------- H. sanctum + guest ----------

    public function test_h_sanctum_token_follows_isolation(): void
    {
        $a = $this->makeBranch('SC1');
        $b = $this->makeBranch('SC2');
        $sys = $this->makeUser('super_admin');
        $f = $this->seedFixtures($a, $b, $a, $sys);

        // Authenticated low-privilege token follows same isolation.
        $low = $this->makeUser('kasir', [$a->id]);
        $token = $low->createToken('t')->plainTextToken;
        $this->getJson('/api/v1/customers', ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/customers/'.$f['custB']->id)->assertNotFound();
    }

    public function test_h_guest_rejected(): void
    {
        $a = $this->makeBranch('SC3');
        $b = $this->makeBranch('SC4');
        $sys = $this->makeUser('super_admin');
        $f = $this->seedFixtures($a, $b, $a, $sys);

        // Unauthenticated remains rejected (fresh app per test => no user).
        $this->getJson('/api/v1/customers')->assertUnauthorized();
        $this->getJson('/api/v1/invoices/'.$f['invA']->id)->assertUnauthorized();
    }

    // ---------- BranchAccess unit ----------

    public function test_branch_access_value_object(): void
    {
        $this->assertTrue(BranchAccess::unrestricted()->canAccess(1));
        $this->assertTrue(BranchAccess::unrestricted()->canAccess(null));
        $this->assertTrue(BranchAccess::restricted([1])->canAccess(1));
        $this->assertFalse(BranchAccess::restricted([1])->canAccess(2));
        $this->assertFalse(BranchAccess::restricted([1])->canAccess(null));
        $this->assertTrue(BranchAccess::denied()->isDenied());
        $this->assertFalse(BranchAccess::denied()->canAccess(1));
        $this->assertFalse(BranchAccess::denied()->canAccess(null));
    }
}
