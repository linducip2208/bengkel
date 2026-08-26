<?php

namespace App\Models;

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
        return $this->hasAnyRole(static::unrestrictedBranchRoles());
    }

    /**
     * May this user read/write data of the given branch?
     * No assignments = legacy behaviour (all branches visible).
     */
    public function hasBranchAccess(?int $branchId): bool
    {
        if ($branchId === null || $this->hasUnrestrictedBranchAccess()) {
            return true;
        }

        $assigned = $this->branches()->pluck('branches.id');

        // Legacy accounts without explicit assignments keep global access.
        if ($assigned->isEmpty()) {
            return true;
        }

        return $assigned->contains($branchId);
    }

    /**
     * Branch IDs this user may access; empty collection = unrestricted.
     */
    public function accessibleBranchIds()
    {
        if ($this->hasUnrestrictedBranchAccess()) {
            return collect();
        }

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
