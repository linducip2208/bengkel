<?php

namespace App\Models;

use App\Support\BranchAccess;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'avatar', 'is_active', 'email_verified_at', 'base_salary'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function dashboardConfig(): HasOne
    {
        return $this->hasOne(DashboardConfig::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(TechnicianSkill::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    /**
     * Roles that always see every branch (owners/administrators).
     */
    public static function unrestrictedBranchRoles(): array
    {
        return ['super_admin', 'admin'];
    }

    public function hasUnrestrictedBranchAccess(): bool
    {
        // Guard-explicit: Sanctum API tokens authenticate via the `sanctum`
        // guard while roles live on `web`. Without this, Spatie throws
        // RoleDoesNotExist under API auth instead of returning false.
        return $this->hasAnyRole(static::unrestrictedBranchRoles(), 'web');
    }

    /**
     * Explicit branch authorization state (FAIL-CLOSED).
     *
     * - super_admin/admin => BranchAccess::unrestricted()
     * - restricted + assignments => BranchAccess::restricted(ids)
     * - restricted + zero assignments => BranchAccess::denied()
     */
    public function branchAccess(): BranchAccess
    {
        if ($this->hasUnrestrictedBranchAccess()) {
            return BranchAccess::unrestricted();
        }

        $assigned = $this->branches()->pluck('branches.id');

        if ($assigned->isEmpty()) {
            return BranchAccess::denied();
        }

        return BranchAccess::restricted($assigned);
    }

    /**
     * May this user read/write data of the given branch? FAIL-CLOSED.
     *
     * - NULL branch rows are global/legacy: only unrestricted users may access them.
     * - Restricted users with zero assignments may access nothing.
     */
    public function hasBranchAccess(?int $branchId): bool
    {
        return $this->branchAccess()->canAccess($branchId);
    }

    /**
     * Assigned branch IDs only. NEVER a sentinel: empty means NO access
     * for restricted users (unrestricted is reported via branchAccess()).
     */
    public function accessibleBranchIds()
    {
        return $this->branches()->pluck('branches.id');
    }

    /**
     * Query builder untuk staff bengkel (mekanik + service_advisor).
     * Fallback ke mekanik saja jika role service_advisor belum di-seed.
     */
    public static function workshopStaffQuery()
    {
        try {
            return static::role(['mekanik', 'service_advisor']);
        } catch (RoleDoesNotExist $e) {
            return static::role('mekanik');
        }
    }
}
