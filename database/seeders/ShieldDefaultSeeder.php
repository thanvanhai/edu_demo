<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShieldDefaultSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa cache permission trước khi seed
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ✅ 1. Danh sách quyền mặc định
        $permissions = [
            'view_users',
            'create_users',
            'update_users',
            'delete_users',

            'view_roles',
            'create_roles',
            'update_roles',
            'delete_roles',

            'view_permissions',
            'create_permissions',
            'update_permissions',
            'delete_permissions',
        ];

        // ✅ 2. Tạo các quyền (bảng permissions)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        // ✅ 3. Tạo Role Super Admin (bảng roles)
        $adminRole = Role::firstOrCreate([
            'name'       => 'super_admin',
            'guard_name' => 'web',
        ]);

        // ✅ 4. Gán tất cả quyền cho super_admin (bảng role_has_permissions)
        $adminRole->syncPermissions($permissions);

        // ✅ 5. Tạo tài khoản Super Admin (bảng users)
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('password'), // 🔥 đổi khi deploy
            ]
        );

        // ✅ 6. Gán Role Super Admin cho tài khoản admin (bảng model_has_roles)
        if (! $adminUser->hasRole('super_admin')) {
            $adminUser->assignRole('super_admin');
        }

        // ✅ 7. Tạo tài khoản thường haicoi (bảng users)
        $normalUser = User::firstOrCreate(
            ['email' => 'haicoi@example.com'],
            [
                'name'     => 'haicoi',
                'password' => bcrypt('password123'),
            ]
        );

        // ✅ 8. Gán quyền trực tiếp cho haicoi (bảng model_has_permissions)
        $directPermissions = ['view_users', 'view_roles']; 
        $normalUser->syncPermissions($directPermissions);

        $this->command->info('✅ Default permissions, Super Admin & haicoi user created successfully!');
    }
}
