<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * RoleSeeder
 * 
 * Seeds default system roles
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full system access with all permissions',
            ],
            [
                'name' => 'Society Admin',
                'slug' => 'society-admin',
                'description' => 'Manage specific society operations',
            ],
            [
                'name' => 'Resident',
                'slug' => 'resident',
                'description' => 'Standard resident access',
            ],
            [
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'Security and maintenance staff',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}