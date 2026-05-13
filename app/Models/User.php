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
        return $this->roles()->where('slug', $roleName)->exists();
    }

    /**
     * Check if user has any of given roles
     * 
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
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

        return $this->roles()->whereHas('permissions', function ($query) use ($permissionSlug) {
            $query->where('slug', $permissionSlug);
        })->exists();
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
