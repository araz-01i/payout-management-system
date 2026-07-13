<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CompletePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $permissions = [
            // Payout permissions
            'view payouts',
            'create payouts',
            'edit payouts',
            'delete payouts',
            'change payout status',

            // Employee permissions
            'manage employees',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        // Admin gets all permissions
        $adminRole->syncPermissions($permissions);

        // Staff gets limited permissions
        $staffRole->syncPermissions([
            'view payouts',
            'create payouts',
            // Staff cannot create, edit, delete users
            // Staff cannot change payout status or manage employees
        ]);
    }
}
