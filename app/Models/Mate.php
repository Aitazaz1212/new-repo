<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mate extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'name',
        'num',
        'rate',
        'matePaid',
        'active'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'matePaid' => 'integer',
        'active' => 'integer'
    ];

    /**
     * Scope a query to only include active mates.
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Scope a query to only include inactive mates.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', 0);
    }

    /**
     * Check if the mate is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->active;
    }
} 