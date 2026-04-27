<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * Role Model
 * 
 * Manages user roles for RBAC system
 * Defines access levels: super-admin, society-admin, resident, staff
 */

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    // Relationships

    /**
     * Get all users with this role
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    /**
     * Get all permissions for this role
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    // Helper Methods

    /**
     * Assign permission to role
     * 
     * @param Permission|int $permission
     * @return void
     */
    public function givePermission($permission): void
    {
        $permissionId = is_object($permission) ? $permission->id : $permission;
        
        if (!$this->permissions()->where('permission_id', $permissionId)->exists()) {
            $this->permissions()->attach($permissionId);
        }
    }

     /**
     * Remove permission from role
     * 
     * @param Permission|int $permission
     * @return void
     */
    public function revokePermission($permission): void
    {
        $permissionId = is_object($permission) ? $permission->id : $permission;
        $this->permissions()->detach($permissionId);
    }

     /**
     * Check if role has specific permission
     * 
     * @param string $permissionSlug
     * @return bool
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

}
