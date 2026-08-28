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
        'service-package' => ['view', 'create', 'edit', 'delete'],
        'jobcard' => ['view', 'create', 'edit', 'delete', 'print'],
        'gate-pass' => ['view', 'create', 'edit', 'delete', 'print'],
        'invoice' => ['view', 'create', 'edit', 'delete', 'pdf', 'send-wa', 'send-email'],
        'product' => ['view', 'create', 'edit', 'delete', 'stock-opname', 'stock-adjust', 'import'],
        'supplier' => ['view', 'create', 'edit', 'delete'],
        'purchase' => ['view', 'create', 'edit', 'delete', 'receive', 'cancel'],
        'purchase-order' => ['view', 'create', 'edit', 'delete', 'receive', 'cancel', 'approve', 'return'],
        'inventory' => ['view', 'adjust', 'opname', 'transfer', 'audit'],
        'equipment' => ['view', 'create', 'edit', 'delete'],
        'warehouse' => ['view', 'create', 'edit', 'delete'],
        'stock-transfer' => ['view', 'create'],
        'pos' => ['view', 'open', 'close', 'checkout', 'discount', 'price_override'],
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

    private const SUPER_ONLY = ['user.delete', 'role.delete', 'role.create', 'role.edit', 'settings.edit', 'backup.download'];

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
        $admin->syncPermissions(Permission::whereNotIn('name', self::SUPER_ONLY)->pluck('name')->toArray());

        // manager: view semua + edit/create transaksi + reports + marketing
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerPerms = Permission::where('name', 'not like', '%.delete')
            ->whereNotIn('name', self::SUPER_ONLY)
            ->whereNotIn('name', ['user.create', 'user.edit', 'user.delete', 'role.view'])
            ->pluck('name')->toArray();
        $manager->syncPermissions($managerPerms);

        // kasir: POS + sales + invoice + customer view + product view
        $kasir = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);
        $kasir->syncPermissions([
            'dashboard.view',
            'pos.view', 'pos.open', 'pos.close', 'pos.checkout',
            'sale.view', 'sale.create', 'sale.edit',
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.pdf', 'invoice.send-wa',
            'customer.view', 'customer.create',
            'vehicle.view', 'vehicle.create',
            'product.view',
            'equipment.view',
            'report.view',
        ]);

        // mekanik: service + jobcard + inventory + equipment + washbay
        $mekanik = Role::firstOrCreate(['name' => 'mekanik', 'guard_name' => 'web']);
        $mekanik->syncPermissions([
            'dashboard.view',
            'service.view', 'service.edit', 'service.complete', 'service.checkout',
            'service-package.view',
            'jobcard.view', 'jobcard.edit', 'jobcard.print',
            'vehicle.view',
            'product.view', 'product.stock-opname',
            'equipment.view',
            'gate-pass.view', 'gate-pass.create',
            'washbay.view',
            'subcontractor.view',
        ]);

        // service_advisor: front-office + booking + service monitoring (no create/delete)
        $serviceAdvisor = Role::firstOrCreate(['name' => 'service_advisor', 'guard_name' => 'web']);
        $serviceAdvisor->syncPermissions([
            'dashboard.view',
            'service.view', 'service.edit', 'service.complete', 'service.checkout',
            'service-package.view',
            'jobcard.view', 'jobcard.print',
            'customer.view', 'customer.create',
            'vehicle.view', 'vehicle.create',
            'booking.view', 'booking.create',
            'gate-pass.view', 'gate-pass.create',
            'reminder.view', 'reminder.create',
            'report.view',
        ]);

        // inventory: gudang — produk, stok, opname, adjustment, penerimaan
        $inventory = Role::firstOrCreate(['name' => 'inventory', 'guard_name' => 'web']);
        $inventory->syncPermissions([
            'dashboard.view',
            'product.view', 'product.create', 'product.edit', 'product.stock-adjust', 'product.stock-opname',
            'purchase.view', 'purchase.create', 'purchase.edit',
            'sale.view',
            'supplier.view', 'supplier.create', 'supplier.edit',
            'equipment.view',
            'report.view',
        ]);

        $this->command->info('  Created 7 roles: super_admin, admin, manager, kasir, mekanik, service_advisor, inventory.');

        // Assign roles — syncRoles ensures only the intended role is active
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
}
