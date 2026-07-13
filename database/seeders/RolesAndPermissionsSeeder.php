<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $permissions = [
            'view payouts',
            'create payouts',
            'edit payouts',
            'delete payouts',
            'change payout status',
            'manage employees',
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions); // Admin gets all permissions

        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions([
            'view payouts',
            'create payouts',
        ]); // Staff can only view and create payouts

        // Create / update an admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );
        $adminUser->syncRoles('admin');

        // Create / update a staff user
        $staffUser = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
            ]
        );
        $staffUser->syncRoles('staff');

        // Also assign admin to the default test user if it exists
        $testUser = User::where('email', 'test@example.com')->first();
        if ($testUser) {
            $testUser->syncRoles('admin');
        }
    }
}
