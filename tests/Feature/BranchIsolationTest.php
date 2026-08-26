<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchIsolationTest extends TestCase
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

    private function makeUser(string $role = 'admin'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_branch_scope_hides_other_branch_records(): void
    {
        $branchA = Branch::create(['name' => 'Cabang A', 'is_active' => true]);
        $branchB = Branch::create(['name' => 'Cabang B', 'is_active' => true]);

        Customer::create(['name' => 'A-Cust 1', 'branch_id' => $branchA->id]);
        Customer::create(['name' => 'A-Cust 2', 'branch_id' => $branchA->id]);
        Customer::create(['name' => 'B-Cust', 'branch_id' => $branchB->id]);

        $this->actingAs($this->makeUser())
            ->withSession(['current_branch_id' => $branchA->id])
            ->get('/customers?search=')
            ->assertOk();

        // Scoped query sees only branch A rows.
        $visible = Customer::all()->pluck('name');
        $this->assertEqualsCanonicalizing(['A-Cust 1', 'A-Cust 2'], $visible->all());
    }

    public function test_without_session_context_no_branch_filter_is_applied(): void
    {
        // Legacy/global view: documents the current default-open behaviour so
        // any future tightening of BranchScope is a conscious decision.
        $branchB = Branch::create(['name' => 'Cabang B', 'is_active' => true]);
        Customer::create(['name' => 'B-Cust Only', 'branch_id' => $branchB->id]);

        $this->actingAs($this->makeUser())->get('/customers');

        $this->assertEquals(1, Customer::count());
    }

    public function test_user_with_branch_assignments_cannot_switch_to_other_branch(): void
    {
        $branchA = Branch::create(['name' => 'Cabang A', 'is_active' => true]);
        $branchB = Branch::create(['name' => 'Cabang B', 'is_active' => true]);

        $user = $this->makeUser('kasir');
        $user->branches()->attach($branchB->id);

        $this->actingAs($user);

        // Allowed: own branch.
        $this->post('/branches/switch', ['branch_id' => $branchB->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Forbidden: branch outside assignment.
        $this->post('/branches/switch', ['branch_id' => $branchA->id])
            ->assertForbidden();

        $this->assertEquals($branchB->id, session('current_branch_id'));
    }

    public function test_api_requests_are_scoped_to_assigned_branches(): void
    {
        $branchA = Branch::create(['name' => 'Cabang A', 'is_active' => true]);
        $branchB = Branch::create(['name' => 'Cabang B', 'is_active' => true]);

        Customer::create(['name' => 'A-Only', 'branch_id' => $branchA->id]);
        Customer::create(['name' => 'B-Only', 'branch_id' => $branchB->id]);

        Role::findOrCreate('manager', 'web');
        $apiUser = User::factory()->create(['is_active' => true]);
        $apiUser->assignRole('manager');
        $apiUser->branches()->attach($branchA->id);
        $token = $apiUser->createToken('ci')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/customers?per_page=50');

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('A-Only', $names);
        $this->assertNotContains('B-Only', $names);
    }
}
