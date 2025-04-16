<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Container extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'loaded',
        'eta',
        'containerID',
        'rxd',
        'items',
        'staff_id'
    ];

    protected $casts = [
        'rxd' => 'integer',
        'staff_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ContainerFile::class, 'containerID', 'containerID');
    }
} 