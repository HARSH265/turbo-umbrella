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
        $superAdmin->permissions()->attach($allPermissions->pluck('id'));

        // Society Admin - Most permissions except user management
        $societyAdmin = Role::where('slug', 'society-admin')->first();
        $societyAdminPermissions = Permission::whereNotIn('module', ['users'])->get();
        $societyAdmin->permissions()->attach($societyAdminPermissions->pluck('id'));
        
        // Add specific user permissions
        $societyAdmin->permissions()->attach(
            Permission::whereIn('slug', [
                'users.view',
                'users.create',
                'users.update',
                'maintenance.policy.view',
                'maintenance.policy.create',
            ])->pluck('id')
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
        ])->get();
        $resident->permissions()->attach($residentPermissions->pluck('id'));

        // Staff - Complaint and visitor management
        $staff = Role::where('slug', 'staff')->first();
        $staffPermissions = Permission::whereIn('slug', [
            'complaints.view',
            'complaints.update',
            'visitors.view',
            'visitors.create',
            'visitors.update',
        ])->get();
        $staff->permissions()->attach($staffPermissions->pluck('id'));
    }
}
