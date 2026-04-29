<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * PermissionSeeder
 * 
 * Seeds all system permissions organized by module
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'module' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'users'],

            // Societies
            ['name' => 'View Societies', 'slug' => 'societies.view', 'module' => 'societies'],
            ['name' => 'Create Societies', 'slug' => 'societies.create', 'module' => 'societies'],
            ['name' => 'Update Societies', 'slug' => 'societies.update', 'module' => 'societies'],
            ['name' => 'Delete Societies', 'slug' => 'societies.delete', 'module' => 'societies'],

            // Flats
            ['name' => 'View Flats', 'slug' => 'flats.view', 'module' => 'flats'],
            ['name' => 'Create Flats', 'slug' => 'flats.create', 'module' => 'flats'],
            ['name' => 'Update Flats', 'slug' => 'flats.update', 'module' => 'flats'],
            ['name' => 'Delete Flats', 'slug' => 'flats.delete', 'module' => 'flats'],

            // Complaints
            ['name' => 'View Complaints', 'slug' => 'complaints.view', 'module' => 'complaints'],
            ['name' => 'Create Complaints', 'slug' => 'complaints.create', 'module' => 'complaints'],
            ['name' => 'Update Complaints', 'slug' => 'complaints.update', 'module' => 'complaints'],
            ['name' => 'Assign Complaints', 'slug' => 'complaints.assign', 'module' => 'complaints'],
            ['name' => 'Delete Complaints', 'slug' => 'complaints.delete', 'module' => 'complaints'],

            // Maintenance
            ['name' => 'View Maintenance', 'slug' => 'maintenance.view', 'module' => 'maintenance'],
            ['name' => 'Create Maintenance', 'slug' => 'maintenance.create', 'module' => 'maintenance'],
            ['name' => 'Update Maintenance', 'slug' => 'maintenance.update', 'module' => 'maintenance'],
            ['name' => 'View Maintenance Policy', 'slug' => 'maintenance.policy.view', 'module' => 'maintenance'],
            ['name' => 'Create Maintenance Policy', 'slug' => 'maintenance.policy.create', 'module' => 'maintenance'],

            // Notices
            ['name' => 'View Notices', 'slug' => 'notices.view', 'module' => 'notices'],
            ['name' => 'Create Notices', 'slug' => 'notices.create', 'module' => 'notices'],
            ['name' => 'Update Notices', 'slug' => 'notices.update', 'module' => 'notices'],
            ['name' => 'Delete Notices', 'slug' => 'notices.delete', 'module' => 'notices'],
            ['name' => 'Publish Notices', 'slug' => 'notices.publish', 'module' => 'notices'],
            ['name' => 'Archive Notices', 'slug' => 'notices.archive', 'module' => 'notices'],
            ['name' => 'Pin Notices', 'slug' => 'notices.pin', 'module' => 'notices'],

            // Visitors
            ['name' => 'View Visitors', 'slug' => 'visitors.view', 'module' => 'visitors'],
            ['name' => 'Create Visitors', 'slug' => 'visitors.create', 'module' => 'visitors'],
            ['name' => 'Update Visitors', 'slug' => 'visitors.update', 'module' => 'visitors'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
