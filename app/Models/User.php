<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'avatar', 'is_active', 'email_verified_at', 'base_salary'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

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

    /**
     * Query builder untuk staff bengkel (mekanik + service_advisor).
     * Fallback ke mekanik saja jika role service_advisor belum di-seed.
     */
    public static function workshopStaffQuery()
    {
        try {
            return static::role(['mekanik', 'service_advisor']);
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            return static::role('mekanik');
        }
    }
}
