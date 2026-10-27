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
use Spatie\Permission\Models\Role;
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
        if ($this->is_active === false) {
            return false;
        }

        // Guard-explicit: Sanctum API tokens authenticate via the `sanctum`
        // guard while roles live on `web`. Without this, Spatie throws
        // RoleDoesNotExist under API auth instead of returning false.
        return $this->hasAnyRole(static::unrestrictedBranchRoles(), 'web');
    }

    /**
     * Permission check yang guard-explicit ke `web`.
     *
     * Semua role/permission aplikasi hidup di guard `web`, sementara
     * request API diautentikasi via guard `sanctum` (Authenticate middleware
     * memanggil Auth::shouldUse('sanctum')). Tanpa guard eksplisit, Spatie
     * me-resolve permission ke guard `sanctum` dan SELALU false — edit di
     * /roles jadi tidak berdampak untuk API. Selalu pakai helper ini
     * (jangan $user->can() / hasAnyPermission() tanpa guard) untuk
     * enforcement di middleware, gate, dan controller.
     */
    public function hasWebPermission(string ...$permissions): bool
    {
        if ($this->is_active === false) {
            return false;
        }

        // super_admin selalu lolos (sama seperti Gate::before), tanpa
        // memedulikan isi tabel permissions — penting untuk test & seeding.
        if ($this->hasRole('super_admin', 'web')) {
            return true;
        }

        $owned = $this->getAllPermissions()->pluck('name')->all();

        foreach ($permissions as $permission) {
            if (in_array($permission, $owned, true)) {
                return true;
            }
        }

        return false;
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
     * Query builder untuk role apa pun tanpa throw bila role belum di-seed.
     * Sama seperti HasRoles::scopeRole(), tapi mengembalikan query
     * kosong saat tidak ada role yang cocok (halaman tidak 500).
     */
    public static function withRole(string ...$names)
    {
        $existing = Role::query()
            ->whereIn('name', $names)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        if ($existing === []) {
            return static::query()->whereRaw('0 = 1');
        }

        return static::role($existing);
    }

    /**
     * Query builder untuk staff bengkel (mekanik + service_advisor).
     * Hanya pakai role yang benar-benar ada; tanpa role sama sekali
     * kembalikan query kosong (jangan throw di halaman publik).
     */
    public static function workshopStaffQuery()
    {
        return static::withRole('mekanik', 'service_advisor');
    }
}
