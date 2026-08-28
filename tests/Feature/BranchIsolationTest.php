<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Income;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Branch isolation: stateless (Sanctum) transactional writes must inherit the
 * caller's branch and user-scoped reads must never cross branch boundaries.
 */
class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class]);
    }

    private function makeBranch(string $code): Branch
    {
        return Branch::create(['name' => 'Branch '.$code, 'code' => $code, 'is_active' => true]);
    }

    private function scopedUser(Branch $branch, string $role = 'kasir'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $user->branches()->attach($branch->id);
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_stateless_create_inherits_caller_branch_instead_of_nil(): void
    {
        $branch = $this->makeBranch('A1');
        $user = $this->scopedUser($branch);

        $customer = Customer::create(['name' => 'Cust A', 'phone' => '0811111111']);
        $method = PaymentMethod::create(['payment' => 'Cash', 'slug' => 'cash', 'is_active' => true]);

        $income = Income::create([
            'invoice_number' => 'INV-ISO-001',
            'customer_id' => $customer->id,
            'payment_method_id' => $method->id,
            'amount' => 50000,
            'income_date' => now()->toDateString(),
            'label' => 'Isolation test',
            'created_by' => $user->id,
        ]);

        $this->assertNotNull($income->branch_id, 'Transactional row must carry a branch_id, not NULL.');
        $this->assertEquals($branch->id, $income->branch_id);
    }

    public function test_user_scoped_to_branch_a_cannot_read_branch_b_rows(): void
    {
        $branchA = $this->makeBranch('A2');
        $branchB = $this->makeBranch('B2');
        $this->scopedUser($branchA);

        $customer = Customer::create(['name' => 'Cust B', 'phone' => '0822222222']);
        $method = PaymentMethod::create(['payment' => 'Cash', 'slug' => 'cash', 'is_active' => true]);
        $branchBIncome = Income::withoutGlobalScopes()->create([
            'invoice_number' => 'INV-ISO-B',
            'customer_id' => $customer->id,
            'payment_method_id' => $method->id,
            'amount' => 50000,
            'income_date' => now()->toDateString(),
            'label' => 'Branch B',
            'created_by' => 1,
            'branch_id' => $branchB->id,
        ]);

        // The branch-A scoped user's normal query must not see branch B's row.
        $visible = Income::whereKey($branchBIncome->id)->first();
        $this->assertNull($visible, 'Branch-A scoped user must not see branch-B income.');

        // ... but an unscoped lookup can still read it (authorization is caller-side).
        $this->assertNotNull(Income::withoutGlobalScopes()->find($branchBIncome->id));
    }
}
