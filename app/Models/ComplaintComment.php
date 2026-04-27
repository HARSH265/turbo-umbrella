<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ComplaintComment Model
 * 
 * Manages comment thread for complaints
 * Supports internal notes and public updates
 */

class ComplaintComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'comment',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    // Relationships

    /**
     * Get parent complaint
     */
    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * Get comment author
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    /**
     * Scope to get public comments only
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope to get internal comments only
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }
}
