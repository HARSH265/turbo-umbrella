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
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@ssms.local',
            'phone' => '9999999999',
            'password' => Hash::make('Admin@123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $superAdmin->roles()->attach($superAdminRole->id);

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: admin@ssms.local');
        $this->command->info('Password: Admin@123');
    }
}