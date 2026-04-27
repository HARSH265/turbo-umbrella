<?php

/**
 * IDE Helper for Laravel Models
 * This file helps IDEs understand custom methods
 */

namespace App\Models {

    /**
     * App\Models\User
     *
     * @method bool hasRole(string $roleName)
     * @method bool hasAnyRole(array $roles)
     * @method bool hasPermission(string $permissionSlug)
     * @method bool isSuperAdmin()
     * @method bool isSocietyAdmin()
     * @method bool isResident()
     * @method bool isStaff()
     * @method \App\Models\Flat|null primaryFlat()
     */
    class User extends \Illuminate\Foundation\Auth\User
    {
    }
}