<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Menyinkronkan permission role agar EDIT DI /roles BERDAMPAK.
 *
 * Latar: otorisasi sebelumnya campuran nama role (middleware role:, gate
 * hasAnyRole) + permission Spatie, sehingga mencentang di /roles sering
 * tidak mengubah apa-apa. Perbaikan memindahkan semua enforcement ke
 * permission — migrasi ini memastikan role bawaan memegang permission
 * yang sesuai akses efektifnya hari ini (hanya TAMBAH, tidak mencabut,
 * supaya customisasi via UI tidak hilang).
 */
return new class extends Migration
{
    private const NEW_PERMISSIONS = [
        'payment.process',
        'stock-adjustment.approve',
        'purchase-requisition.view',
        'purchase-requisition.create',
        'purchase-requisition.submit',
        'purchase-requisition.approve',
        'purchase-requisition.delete',
        'media.upload',
        'media.delete',
    ];

    private const GRANTS = [
        'admin' => [
            'settings.edit', 'backup.download', 'user.delete',
            'payment.process', 'stock-adjustment.approve',
            'purchase-requisition.view', 'purchase-requisition.create',
            'purchase-requisition.submit', 'purchase-requisition.approve',
            'purchase-requisition.delete',
            'media.upload', 'media.delete',
            'pos.discount',
        ],
        'manager' => [
            'payment.process', 'stock-adjustment.approve',
            'purchase-requisition.view', 'purchase-requisition.create',
            'purchase-requisition.submit', 'purchase-requisition.approve',
            'purchase-requisition.delete',
            'media.upload', 'media.delete',
            'pos.discount',
        ],
        'kasir' => [
            'payment.process',
            'purchase-requisition.view',
        ],
        'mekanik' => [
            'purchase-requisition.view',
            'gate-pass.print', 'gate-pass.edit',
            'media.upload',
            'qc.view', 'qc.perform',
        ],
        'service_advisor' => [
            'warranty.create',
            'gate-pass.print', 'gate-pass.edit',
            'media.upload', 'media.delete',
            'qc.view', 'qc.perform',
        ],
        'inventory' => [
            'inventory.adjust',
            'purchase-order.view', 'purchase-order.receive', 'purchase.receive',
            'purchase-requisition.view', 'purchase-requisition.create',
            'purchase-requisition.submit',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::NEW_PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::GRANTS as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }
            $missing = array_diff($perms, $role->permissions()->pluck('name')->toArray());
            if (! empty($missing)) {
                $role->givePermissionTo(array_values($missing));
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Additive backfill — tidak di-rollback agar customisasi tidak rusak.
    }
};
