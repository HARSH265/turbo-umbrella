<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SuperAdminSeeder
 * 
 * Creates default super admin account
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SSMS_SUPER_ADMIN_PASSWORD');

        if (!$password) {
            $this->command->warn('Super admin not created. Set SSMS_SUPER_ADMIN_PASSWORD to seed this account.');
            return;
        }

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@ssms.local',
            'phone' => '9999999999',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $superAdmin->roles()->attach($superAdminRole->id);

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: admin@ssms.local');
    }
}
