<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /** Semua modul × akses CRUD. */
    private const MODULES = [
        'dashboard'   => ['view'],
        'branch'      => ['view','create','edit','delete'],
        'holiday'     => ['view','create','edit','delete'],
        'washbay'     => ['view','create','edit','delete'],
        'customer'    => ['view','create','edit','delete','import'],
        'vehicle'     => ['view','create','edit','delete'],
        'service'     => ['view','create','edit','delete','complete','checkout'],
        'jobcard'     => ['view','create','edit','delete','print'],
        'gate-pass'   => ['view','create','edit','delete','print'],
        'invoice'     => ['view','create','edit','delete','pdf','send-wa','send-email'],
        'product'     => ['view','create','edit','delete','stock-opname','stock-adjust','import'],
        'supplier'    => ['view','create','edit','delete'],
        'purchase'    => ['view','create','edit','delete'],
        'pos'         => ['view','open','close'],
        'booking'     => ['view','create','edit','delete'],
        'sale'        => ['view','create','edit','delete'],
        'income'      => ['view','create','edit','delete'],
        'expense'     => ['view','create','edit','delete'],
        'petty-cash'  => ['view','create','delete'],
        'voucher'     => ['view','create','edit','delete'],
        'loyalty'     => ['view','adjust'],
        'review'      => ['view','edit','delete'],
        'warranty'    => ['view','create','edit','delete'],
        'commission'  => ['view','edit','report'],
        'report'      => ['view','export'],
        'currency'    => ['view','create','edit','delete'],
        'country'     => ['view','create','edit','delete'],
        'state'       => ['view','create','edit','delete'],
        'city'        => ['view','create','edit','delete'],
        'notification-template' => ['view','create','edit','delete'],
        'reminder'    => ['view','create','edit','delete','send'],
        'custom-field'=> ['view','create','edit','delete'],
        'payment-gateway' => ['view','create','edit','delete'],
        'two-factor'  => ['view','enable','disable'],
        'stock-history'=> ['view'],
        'email-log'   => ['view','delete'],
        'note'        => ['view','create','edit','delete'],
        'user'        => ['view','create','edit','delete'],
        'role'        => ['view','create','edit','delete'],
        'activity-log'=> ['view'],
        'settings'    => ['view','edit'],

        // Master Data (digabung sebagai group)
        'master-data' => ['view','create','edit','delete'],
    ];

    /** Permission yang hanya untuk super_admin. */
    private const SUPER_ONLY = ['user.delete', 'role.delete', 'role.create', 'role.edit', 'settings.edit'];

    public function run(): void
    {
        $this->command->info('Creating permissions & roles...');

        // ── Hapus cache permission ──
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Buat semua permissions ──
        $allPermissions = [];
        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                $allPermissions[] = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web']
                );
            }
        }
        $this->command->info('  Created ' . count($allPermissions) . ' permissions.');

        // ── Role: super_admin (ALL permissions) ──
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // ── Role: admin (ALL kecuali super-only) ──
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminPerms = Permission::whereNotIn('name', self::SUPER_ONLY)->pluck('name')->toArray();
        $admin->syncPermissions($adminPerms);

        // ── Role: manager (view semua + edit transaksi + reports) ──
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerPerms = Permission::where('name', 'not like', '%.delete')
            ->whereNotIn('name', self::SUPER_ONLY)
            ->whereNotIn('name', ['user.create','user.edit','role.view'])
            ->pluck('name')->toArray();
        $manager->syncPermissions($managerPerms);

        // ── Role: kasir (POS + sales + invoice + payment + customer view) ──
        $kasir = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);
        $kasirPerms = [
            'dashboard.view','pos.view','pos.open','pos.close',
            'sale.view','sale.create','sale.edit',
            'invoice.view','invoice.create','invoice.edit','invoice.pdf','invoice.send-wa',
            'customer.view','customer.create',
            'vehicle.view','vehicle.create',
            'product.view',
            'report.view',
        ];
        $kasir->syncPermissions($kasirPerms);

        // ── Role: mekanik (service + jobcard + inventory view + checklist) ──
        $mekanik = Role::firstOrCreate(['name' => 'mekanik', 'guard_name' => 'web']);
        $mekanikPerms = [
            'dashboard.view',
            'service.view','service.edit','service.complete','service.checkout',
            'jobcard.view','jobcard.edit','jobcard.print',
            'vehicle.view',
            'product.view','product.stock-opname',
            'gate-pass.view',
            'washbay.view',
        ];
        $mekanik->syncPermissions($mekanikPerms);

        $this->command->info('  Created 5 roles: super_admin, admin, manager, kasir, mekanik.');

        // ── Assign roles ke users ──
        $adminUser = \App\Models\User::where('email', 'admin@bengkelpaten.id')->first();
        if ($adminUser) { $adminUser->assignRole('super_admin'); $this->command->info('  admin@bengkelpaten.id → super_admin'); }

        $mekanikUser = \App\Models\User::where('email', 'mekanik@bengkelpaten.id')->first();
        if ($mekanikUser) { $mekanikUser->assignRole('mekanik'); $this->command->info('  mekanik@bengkelpaten.id → mekanik'); }

        // ── Tambah 2 user demo ──
        $managerUser = \App\Models\User::firstOrCreate(
            ['email' => 'manager@bengkelpaten.test'],
            ['name' => 'Manager Cabang', 'password' => bcrypt('password'), 'is_active' => true]
        );
        $managerUser->assignRole('manager');

        $kasirUser = \App\Models\User::firstOrCreate(
            ['email' => 'kasir@bengkelpaten.test'],
            ['name' => 'Kasir Counter', 'password' => bcrypt('password'), 'is_active' => true]
        );
        $kasirUser->assignRole('kasir');

        $this->command->info('  Demo users: manager@bengkelpaten.test / kasir@bengkelpaten.test');
        $this->command->info('Permissions & roles seeded successfully!');
    }
}
