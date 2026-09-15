<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * RolePermissionSeeder
 * 
 * Assigns permissions to roles
 * Defines access control matrix for the system
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin - All permissions
        $superAdmin = Role::where('slug', 'super-admin')->first();
        $allPermissions = Permission::all();
        $superAdmin->permissions()->syncWithoutDetaching($allPermissions->pluck('id')->all());

        // Society Admin - Most permissions except user management
        $societyAdmin = Role::where('slug', 'society-admin')->first();
        $societyAdminPermissions = Permission::whereNotIn('module', ['users'])->get();
        $societyAdmin->permissions()->syncWithoutDetaching($societyAdminPermissions->pluck('id')->all());
        
        // Add specific user permissions
        $societyAdmin->permissions()->syncWithoutDetaching(
            Permission::whereIn('slug', [
                'users.view',
                'users.create',
                'users.update',
                'maintenance.policy.view',
                'maintenance.policy.create',
            ])->pluck('id')->all()
        );

// Resident - Limited permissions
        $resident = Role::where('slug', 'resident')->first();
        $residentPermissions = Permission::whereIn('slug', [
            'complaints.view',
            'complaints.create',
            'maintenance.view',
            'notices.view',
            'visitors.view',
            'visitors.update', // Can approve their own visitors
            'amenities.view',
            'flats.view',
            'vehicles.view',
        ])->get();
        $resident->permissions()->syncWithoutDetaching($residentPermissions->pluck('id')->all());

        // Staff - Complaint and visitor management
        $staff = Role::where('slug', 'staff')->first();
        $staffPermissions = Permission::whereIn('slug', [
            'complaints.view',
            'complaints.update',
            'notices.view',
            'visitors.view',
            'visitors.create',
            'visitors.update',
            'amenities.view',
            'vehicles.view',
            'vehicles.create',
        ])->get();
        $staff->permissions()->syncWithoutDetaching($staffPermissions->pluck('id')->all());

        // Society Admin - Add vehicles and amenities permissions
        $societyAdmin->permissions()->syncWithoutDetaching(
            Permission::whereIn('slug', [
                'vehicles.view',
                'vehicles.create',
                'vehicles.update',
                'vehicles.delete',
                'amenities.view',
                'amenities.create',
                'amenities.update',
                'amenities.delete',
            ])->pluck('id')->all()
        );
    }
}
