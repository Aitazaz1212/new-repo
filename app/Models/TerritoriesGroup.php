<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerritoriesGroup extends Model
{
    protected $fillable = [
        'name',
        'warehouse_id'
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the territories belonging to this group.
     */
    public function territories(): HasMany
    {
        return $this->hasMany(Territory::class, 'group_id');
    }

    /**
     * Get the warehouse this group belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
