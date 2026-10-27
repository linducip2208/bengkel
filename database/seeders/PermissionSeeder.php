<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    private const MODULES = [
        'dashboard' => ['view'],
        'company' => ['view', 'create', 'edit', 'delete'],
        'branch' => ['view', 'create', 'edit', 'delete'],
        'holiday' => ['view', 'create', 'edit', 'delete'],
        'washbay' => ['view', 'create', 'edit', 'delete'],
        'customer' => ['view', 'create', 'edit', 'delete', 'import'],
        'customer-group' => ['view', 'create', 'edit', 'delete'],
        'vehicle' => ['view', 'create', 'edit', 'delete'],
        'service' => ['view', 'create', 'edit', 'delete', 'complete', 'checkout'],
        'estimates' => ['view', 'create', 'update', 'send', 'revise', 'override', 'convert_invoice'],
        'findings' => ['view', 'create', 'update', 'resolve'],
        'work-packages' => ['view', 'create', 'update'],
        'work-tasks' => ['view', 'start', 'finish'],
        'qc' => ['view', 'perform'],
        'service-package' => ['view', 'create', 'edit', 'delete'],
        'jobcard' => ['view', 'create', 'edit', 'delete', 'print'],
        'gate-pass' => ['view', 'create', 'edit', 'delete', 'print'],
        'invoice' => ['view', 'create', 'edit', 'delete', 'void', 'pdf', 'send-wa', 'send-email'],
        'product' => ['view', 'create', 'edit', 'delete', 'stock-opname', 'stock-adjust', 'import'],
        'supplier' => ['view', 'create', 'edit', 'delete'],
        'purchase' => ['view', 'create', 'edit', 'delete', 'receive', 'cancel'],
        'purchase-order' => ['view', 'create', 'edit', 'delete', 'receive', 'cancel', 'approve', 'return'],
        'inventory' => ['view', 'adjust', 'opname', 'transfer', 'audit'],
        'equipment' => ['view', 'create', 'edit', 'delete'],
        'warehouse' => ['view', 'create', 'edit', 'delete'],
        'stock-transfer' => ['view', 'create'],
        'pos' => ['view', 'open', 'close', 'checkout', 'discount', 'price_override'],
        'payment' => ['process'],
        'stock-adjustment' => ['approve'],
        'purchase-requisition' => ['view', 'create', 'submit', 'approve', 'delete'],
        'media' => ['upload', 'delete'],
        'booking' => ['view', 'create', 'edit', 'delete'],
        'sale' => ['view', 'create', 'edit', 'delete'],
        'income' => ['view', 'create', 'edit', 'delete'],
        'expense' => ['view', 'create', 'edit', 'delete'],
        'petty-cash' => ['view', 'create', 'delete'],
        'voucher' => ['view', 'create', 'edit', 'delete'],
        'loyalty' => ['view', 'adjust'],
        'review' => ['view', 'edit', 'delete'],
        'blog' => ['view', 'create', 'edit', 'delete'],
        'campaign' => ['view', 'send'],
        'warranty' => ['view', 'create', 'edit', 'delete'],
        'insurance-claim' => ['view', 'create', 'edit', 'delete'],
        'fleet-contract' => ['view', 'create', 'edit', 'delete'],
        'subcontractor' => ['view', 'create', 'edit', 'delete'],
        'commission' => ['view', 'edit', 'report', 'mark_paid'],
        'report' => ['view', 'export'],
        'currency' => ['view', 'create', 'edit', 'delete'],
        'country' => ['view', 'create', 'edit', 'delete'],
        'state' => ['view', 'create', 'edit', 'delete'],
        'city' => ['view', 'create', 'edit', 'delete'],
        'notification-template' => ['view', 'create', 'edit', 'delete'],
        'reminder' => ['view', 'create', 'edit', 'delete', 'send'],
        'custom-field' => ['view', 'create', 'edit', 'delete'],
        'payment-gateway' => ['view', 'create', 'edit', 'delete'],
        'two-factor' => ['view', 'enable', 'disable'],
        'stock-history' => ['view'],
        'email-log' => ['view', 'delete'],
        'note' => ['view', 'create', 'edit', 'delete'],
        'user' => ['view', 'create', 'edit', 'delete'],
        'role' => ['view', 'create', 'edit', 'delete'],
        'activity-log' => ['view'],
        'api-token' => ['view', 'create', 'revoke'],
        'backup' => ['view', 'download'],
        'settings' => ['view', 'edit'],
        'finance-coa' => ['view', 'create', 'delete'],
        'finance-journal' => ['view', 'create'],
        'master-data' => ['view', 'create', 'edit', 'delete'],
    ];

    /**
     * SUPER_ONLY = permission yang TIDAK ikut ke role admin.
     * user.delete / settings.edit / backup.download sengaja TIDAK super-only:
     * route web sudah lama mengizinkan admin (middleware role), jadi permission
     * harus mencerminkan akses efektif — kalau tidak, edit di /roles berbohong.
     */
    private const SUPER_ONLY = ['role.delete', 'role.create', 'role.edit'];

    public function run(): void
    {
        $this->command->info('Creating permissions & roles...');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => 'web']);
            }
        }
        $total = Permission::count();
        $this->command->info("  Created {$total} permissions.");

        // super_admin: ALL
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // admin: ALL kecuali super-only
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        self::assignRolePermissions($admin, Permission::whereNotIn('name', self::SUPER_ONLY)->pluck('name')->toArray());

        // manager: view semua + edit/create transaksi + reports + marketing
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerPerms = Permission::where('name', 'not like', '%.delete')
            ->whereNotIn('name', self::SUPER_ONLY)
            ->whereNotIn('name', ['user.create', 'user.edit', 'user.delete', 'role.view'])
            ->pluck('name')->toArray();
        self::assignRolePermissions($manager, $managerPerms);

        foreach (['kasir', 'mekanik', 'service_advisor', 'inventory'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            self::assignRolePermissions($role, self::fixedRolePermissions()[$roleName]);
        }

        $this->command->info('  Created 7 roles: super_admin, admin, manager, kasir, mekanik, service_advisor, inventory.');

        // Assign users — syncRoles ensures only the intended role is active
        $adminUser = User::firstOrCreate(['email' => 'admin@bengkel.test'], ['name' => 'Administrator', 'password' => bcrypt('password'), 'is_active' => true]);
        $adminUser->syncRoles('super_admin');
        $this->command->info('  admin@bengkel.test → super_admin');

        $mekanikUser = User::firstOrCreate(['email' => 'teknisi@bengkel.test'], ['name' => 'Teknisi Bengkel', 'password' => bcrypt('password'), 'is_active' => true]);
        $mekanikUser->syncRoles('mekanik');
        $this->command->info('  teknisi@bengkel.test → mekanik');

        User::firstOrCreate(['email' => 'manager@bengkel.test'], ['name' => 'Manager Cabang', 'password' => bcrypt('password'), 'is_active' => true])->syncRoles('manager');
        User::firstOrCreate(['email' => 'kasir@bengkel.test'], ['name' => 'Kasir Counter', 'password' => bcrypt('password'), 'is_active' => true])->syncRoles('kasir');
        User::firstOrCreate(['email' => 'kasir2@bengkel.test'], ['name' => 'Kasir 2', 'password' => bcrypt('password'), 'is_active' => true])->syncRoles('kasir');
        User::firstOrCreate(['email' => 'sa@bengkel.test'], ['name' => 'Service Advisor', 'password' => bcrypt('password'), 'is_active' => true])->syncRoles('service_advisor');

        $this->command->info('  Demo users ready: admin / manager / kasir / teknisi / sales @bengkel.test');
    }

    /**
     * Beri permission ke role TANPA menghapus customisasi dari /roles.
     * - Role baru: sync persis defaults.
     * - Role lama (mis. setelah update deploy + reseed): hanya TAMBAH yang kurang,
     *   permission yang dicabut/ditambah manual via UI tetap dipertahankan.
     */
    private static function assignRolePermissions(Role $role, array $permissions): void
    {
        if ($role->wasRecentlyCreated) {
            $role->syncPermissions($permissions);

            return;
        }

        $missing = array_diff($permissions, $role->permissions()->pluck('name')->toArray());
        if (! empty($missing)) {
            $role->givePermissionTo(array_values($missing));
        }
    }

    /**
     * Semua nama permission yang dikenal aplikasi (single source of truth
     * untuk seeder DAN test).
     */
    public static function allPermissionNames(): array
    {
        $names = [];
        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }

    /**
     * Daftar permission default per role (single source of truth untuk
     * seeder DAN test). Admin/manager dihitung dinamis di run().
     */
    public static function fixedRolePermissions(): array
    {
        return [
            'kasir' => [
                'dashboard.view',
                'pos.view', 'pos.open', 'pos.close', 'pos.checkout',
                'payment.process',
                'sale.view', 'sale.create', 'sale.edit',
                'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.pdf', 'invoice.send-wa',
                'estimates.view', 'estimates.convert_invoice',
                'customer.view', 'customer.create',
                'vehicle.view', 'vehicle.create',
                'product.view',
                'equipment.view',
                'report.view',
                'purchase-requisition.view',
            ],
            'mekanik' => [
                'dashboard.view',
                'service.view', 'service.edit', 'service.complete', 'service.checkout',
                'service-package.view',
                'estimates.view',
                'findings.view', 'findings.create',
                'work-packages.view',
                'work-tasks.view', 'work-tasks.start', 'work-tasks.finish',
                'qc.view', 'qc.perform',
                'jobcard.view', 'jobcard.edit', 'jobcard.print',
                'vehicle.view',
                'product.view', 'product.stock-opname',
                'equipment.view',
                'gate-pass.view', 'gate-pass.create', 'gate-pass.print', 'gate-pass.edit',
                'washbay.view',
                'subcontractor.view',
                'media.upload',
                'purchase-requisition.view',
            ],
            'service_advisor' => [
                'dashboard.view',
                'service.view', 'service.edit', 'service.complete', 'service.checkout',
                'service-package.view',
                'estimates.view', 'estimates.create', 'estimates.update', 'estimates.send', 'estimates.revise',
                'findings.view', 'findings.create', 'findings.update', 'findings.resolve',
                'work-packages.view', 'work-packages.create', 'work-packages.update',
                'work-tasks.view',
                'qc.view', 'qc.perform',
                'jobcard.view', 'jobcard.print',
                'customer.view', 'customer.create',
                'vehicle.view', 'vehicle.create',
                'booking.view', 'booking.create', 'booking.edit',
                'gate-pass.view', 'gate-pass.create', 'gate-pass.print', 'gate-pass.edit',
                'warranty.create',
                'media.upload', 'media.delete',
                'reminder.view', 'reminder.create',
                'report.view',
            ],
            'inventory' => [
                'dashboard.view',
                'inventory.adjust',
                'product.view', 'product.create', 'product.edit', 'product.stock-adjust', 'product.stock-opname',
                'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.receive',
                'purchase-order.view', 'purchase-order.receive',
                'purchase-requisition.view', 'purchase-requisition.create', 'purchase-requisition.submit',
                'sale.view',
                'estimates.view',
                'findings.view',
                'work-packages.view',
                'supplier.view', 'supplier.create', 'supplier.edit',
                'equipment.view',
                'report.view',
            ],
        ];
    }

    /**
     * Beri user permission produksi sesuai role-nya (untuk test).
     * Additive + idempotent: custom role tanpa entri = tidak diubah.
     */
    public static function grantProductionPermissions(User $user): void
    {
        // Pastikan katalog permission lengkap dulu (DB test bisa sparse).
        $allKnown = array_unique(self::allPermissionNames());
        $existing = Permission::pluck('name')->all();
        foreach (array_diff($allKnown, $existing) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $fresh = $user->fresh();
        foreach ($fresh->roles as $role) {
            $roleName = (string) $role->getAttribute('name');
            $permissions = match ($roleName) {
                // super_admin lolos via Gate::before + hasWebPermission,
                // tanpa perlu baris permission satu per satu.
                'super_admin' => [],
                // admin/manager: seluruh katalog permission (findOrCreate
                // agar lengkap walau DB test masih sparse), minus pengecualian.
                'admin' => array_diff(
                    self::allPermissionNames(),
                    self::SUPER_ONLY,
                ),
                'manager' => array_values(array_diff(
                    array_filter(
                        self::allPermissionNames(),
                        fn ($name) => ! str_ends_with($name, '.delete'),
                    ),
                    array_merge(self::SUPER_ONLY, ['user.create', 'user.edit', 'user.delete', 'role.view']),
                )),
                default => self::fixedRolePermissions()[$roleName] ?? [],
            };
            $models = [];
            foreach ($permissions as $name) {
                $models[] = Permission::findOrCreate($name, 'web');
            }
            if ($models) {
                $fresh->givePermissionTo(collect($models));
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
