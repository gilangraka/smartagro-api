<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SpatieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['Superadmin', 'Admin', 'User'];
        $permissions = ['manage_user', 'delete_article', 'delete_discuss', 'action_master'];
        $role_has_permission = [
            1 => [1, 2, 3, 4],
            2 => [1, 2, 3, 4]
        ];

        foreach ($roles as $key => $value) {
            $data = new Role([
                'name' => $value
            ]);
            $data->save();
        }
        foreach ($permissions as $key => $value) {
            $data = new Permission([
                'name' => $value
            ]);
            $data->save();
        }
        foreach ($role_has_permission as $roleId => $permissionIds) {
            $role = Role::find($roleId);
            $role->givePermissionTo($permissionIds);
        }

        $user = new User([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('superadmin')
        ]);
        $user->save();
        $user->assignRole('Superadmin');
    }
}
