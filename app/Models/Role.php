<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description'
    ];

    protected $appends = ['role_name', 'role_id'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function getRoleNameAttribute()
    {
        return $this->slug;
    }

    public function getRoleIdAttribute()
    {
        return $this->id;
    }
}
