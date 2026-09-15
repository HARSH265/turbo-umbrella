<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'profile_photo',
        'society_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Per-request memo for role/permission slugs.
     *
     * hasRole()/hasPermission() are called dozens of times per request (the sidebar
     * alone makes 17 checks, and Gate::before fires on every @can and authorize()).
     * Without this each call was a fresh query. Not persisted — cleared whenever the
     * user's roles change via flushAccessCache().
     */
    protected ?array $roleSlugCache = null;

    protected ?array $permissionSlugCache = null;

    // ========================================
    // RELATIONSHIPS
    // ========================================
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function flats()
    {
        return $this->belongsToMany(Flat::class, 'flat_residents')
            ->withPivot(['relation_type', 'start_date', 'end_date', 'is_primary', 'is_active'])
            ->withTimestamps();
    }

    public function activeFlats()
    {
        return $this->flats()->wherePivot('is_active', true);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function assignedComplaints()
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }

    // ========================================
    // HELPER METHODS (ADD THESE IF MISSING)
    // ========================================

    /**
     * Check if user has specific role
     * 
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return in_array($roleName, $this->roleSlugs(), true);
    }

    /**
     * Check if user has any of given roles
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($roles, $this->roleSlugs())) > 0;
    }

    /**
     * All role slugs for this user, resolved once per request.
     *
     * @return string[]
     */
    public function roleSlugs(): array
    {
        return $this->roleSlugCache ??= $this->roles()->pluck('slug')->all();
    }

    /**
     * All permission slugs granted by this user's roles, resolved once per request.
     *
     * @return string[]
     */
    public function permissionSlugs(): array
    {
        return $this->permissionSlugCache ??= $this->roles()
            ->with('permissions:id,slug')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Drop the memoized role/permission slugs.
     *
     * Call after changing a user's role assignments so later checks in the same
     * request see the new state.
     */
    public function flushAccessCache(): static
    {
        $this->roleSlugCache = null;
        $this->permissionSlugCache = null;
        $this->unsetRelation('roles');

        return $this;
    }

/**
     * Check if user has a specific permission
     * 
     * @param string $permissionSlug
     * @return bool
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permissionSlug, $this->permissionSlugs(), true);
    }

    /**
     * Get primary flat for user
     * 
     * @return Flat|null
     */
    public function primaryFlat()
    {
        return $this->flats()->wherePivot('is_primary', true)->first();
    }

    /**
     * Check if user is a super admin
     * 
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is a society admin
     * 
     * @return bool
     */
    public function isSocietyAdmin(): bool
    {
        return $this->hasRole('society-admin');
    }

    /**
     * Check if user is a resident
     * 
     * @return bool
     */
    public function isResident(): bool
    {
        return $this->hasRole('resident');
    }

    /**
     * Check if user is staff
     * 
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to get only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get users by role
     */
    public function scopeWithRole($query, string $roleName)
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('slug', $roleName);
        });
    }
}
