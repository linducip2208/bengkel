<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchCodeUniquenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class, PreventRequestForgery::class]);
        $this->actingAs(User::factory()->create(['is_active' => true]));
    }

    public function test_code_is_normalized_before_validation_and_current_code_can_be_retained(): void
    {
        $branch = Branch::create(['name' => 'Pusat', 'code' => ' Jkt 01 ']);
        $this->assertSame('JKT 01', $branch->code);

        $before = Branch::count();
        $this->put(route('branches.update', $branch), ['name' => 'Pusat Baru', 'code' => ' jkt 01 '])
            ->assertSessionHasNoErrors();

        $this->assertSame($branch->id, $branch->fresh()->id);
        $this->assertSame($before, Branch::count());
    }

    public function test_normalization_variant_cannot_take_another_branch_code(): void
    {
        Branch::create(['name' => 'Pusat', 'code' => 'JKT01']);

        $this->post(route('branches.store'), ['name' => 'Cabang', 'code' => ' jkt01 '])
            ->assertSessionHasErrors('code');
        $this->assertSame(1, Branch::count());
    }
}
