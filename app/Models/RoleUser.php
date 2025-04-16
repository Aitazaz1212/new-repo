<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RoleUser extends Pivot
{
    protected $table = 'role_user';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'role_id' => 'integer'
    ];

    /**
     * Get the user that owns the role.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role that belongs to the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Scope a query to find all roles for a user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to find all users for a role.
     */
    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }
} 