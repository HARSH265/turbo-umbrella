<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\Flat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $societyAdminRole = Role::where('slug', 'society-admin')->first();
        $residentRole = Role::where('slug', 'resident')->first();
        $staffRole = Role::where('slug', 'staff')->first();

        // Create test society, tower, and flats if they don't exist
        $society = Society::firstOrCreate(
            ['code' => 'TEST001'],
            [
                'name' => 'Green Valley Apartments',
                'address' => '123 Main Street, Sector 5',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'contact_number' => '9876543210',
                'email' => 'info@greenvalley.com',
                'is_active' => true,
                'created_by' => 1,
            ]
        );

        $tower = Tower::firstOrCreate(
            ['society_id' => $society->id, 'name' => 'Tower A'],
            [
                'total_floors' => 10,
                'is_active' => true,
                'created_by' => 1,
            ]
        );

        // Create flats
        $flats = [];
        for ($i = 1; $i <= 5; $i++) {
            $flats[] = Flat::firstOrCreate(
                ['tower_id' => $tower->id, 'flat_number' => "A-10{$i}"],
                [
                    'floor_number' => 1,
                    'type' => '2BHK',
                    'carpet_area' => 1200,
                    'occupancy_status' => 'occupied',
                    'is_active' => true,
                    'created_by' => 1,
                ]
            );
        }

        // 1. Society Admin User
        $societyAdmin = User::firstOrCreate(
            ['email' => 'societyadmin@ssms.local'],
            [
                'name' => 'Society Admin',
                'phone' => '9876543211',
                'society_id' => $society->id,
                'password' => Hash::make('Admin@123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'created_by' => 1,
            ]
        );
        $societyAdmin->forceFill([
            'society_id' => $society->id,
            'is_active' => true,
        ])->save();
        if (!$societyAdmin->roles()->where('role_id', $societyAdminRole->id)->exists()) {
            $societyAdmin->roles()->attach($societyAdminRole->id);
        }

        // 2. Resident Users (5 residents)
        $residents = [
            ['name' => 'John Doe', 'email' => 'john@ssms.local', 'phone' => '9876543212'],
            ['name' => 'Jane Smith', 'email' => 'jane@ssms.local', 'phone' => '9876543213'],
            ['name' => 'Robert Wilson', 'email' => 'robert@ssms.local', 'phone' => '9876543214'],
            ['name' => 'Emily Brown', 'email' => 'emily@ssms.local', 'phone' => '9876543215'],
            ['name' => 'Michael Davis', 'email' => 'michael@ssms.local', 'phone' => '9876543216'],
        ];

        foreach ($residents as $index => $residentData) {
            $resident = User::firstOrCreate(
                ['email' => $residentData['email']],
                [
                    'name' => $residentData['name'],
                    'phone' => $residentData['phone'],
                    'society_id' => $society->id,
                    'password' => Hash::make('Resident@123'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'created_by' => 1,
                ]
            );
            $resident->forceFill([
                'society_id' => $society->id,
                'is_active' => true,
            ])->save();

            // Assign resident role
            if (!$resident->roles()->where('role_id', $residentRole->id)->exists()) {
                $resident->roles()->attach($residentRole->id);
            }

            // Assign to flat
            if (!$resident->flats()->where('flat_id', $flats[$index]->id)->exists()) {
                $resident->flats()->attach($flats[$index]->id, [
                    'relation_type' => 'owner',
                    'start_date' => now(),
                    'is_primary' => true,
                    'is_active' => true,
                    'created_by' => 1,
                ]);

                // Update flat occupancy
                $flats[$index]->update(['occupancy_status' => 'occupied']);
            }
        }

        // 3. Staff Users (2 staff members)
        $staffMembers = [
            ['name' => 'Security Guard - Ramesh', 'email' => 'ramesh@ssms.local', 'phone' => '9876543217'],
            ['name' => 'Maintenance Staff - Suresh', 'email' => 'suresh@ssms.local', 'phone' => '9876543218'],
        ];

        foreach ($staffMembers as $staffData) {
            $staff = User::firstOrCreate(
                ['email' => $staffData['email']],
                [
                    'name' => $staffData['name'],
                    'phone' => $staffData['phone'],
                    'society_id' => $society->id,
                    'password' => Hash::make('Staff@123'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'created_by' => 1,
                ]
            );
            $staff->forceFill([
                'society_id' => $society->id,
                'is_active' => true,
            ])->save();

            // Assign staff role
            if (!$staff->roles()->where('role_id', $staffRole->id)->exists()) {
                $staff->roles()->attach($staffRole->id);
            }
        }

        $this->command->info('✅ Test users created successfully!');
        $this->command->info('');
        $this->command->info('=== LOGIN CREDENTIALS ===');
        $this->command->info('');
        $this->command->info('🔐 SUPER ADMIN:');
        $this->command->info('   Email: admin@ssms.local');
        $this->command->info('   Password: Admin@123');
        $this->command->info('');
        $this->command->info('🔐 SOCIETY ADMIN:');
        $this->command->info('   Email: societyadmin@ssms.local');
        $this->command->info('   Password: Admin@123');
        $this->command->info('');
        $this->command->info('🔐 RESIDENTS:');
        $this->command->info('   Email: john@ssms.local | Password: Resident@123');
        $this->command->info('   Email: jane@ssms.local | Password: Resident@123');
        $this->command->info('   Email: robert@ssms.local | Password: Resident@123');
        $this->command->info('   Email: emily@ssms.local | Password: Resident@123');
        $this->command->info('   Email: michael@ssms.local | Password: Resident@123');
        $this->command->info('');
        $this->command->info('🔐 STAFF:');
        $this->command->info('   Email: ramesh@ssms.local | Password: Staff@123');
        $this->command->info('   Email: suresh@ssms.local | Password: Staff@123');
        $this->command->info('');
    }
}
