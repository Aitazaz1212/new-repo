<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapacityUnit extends Model
{
    protected $fillable = [
        'capacity_unit',
        'capacity_decimal_precision',
        'capacity_label',
        'volume_units',
        'volume_label',
        'volume_decimal_precision',
        'enable_capacity_constraint_one',
        'enable_capacity_constraint_two'
    ];

    protected $casts = [
        'enable_capacity_constraint_one' => 'boolean',
        'enable_capacity_constraint_two' => 'boolean'
    ];

    public function getFormattedCapacityLabelAttribute(): string
    {
        return $this->capacity_label . ' (' . $this->capacity_unit . ')';
    }

    public function getFormattedVolumeLabelAttribute(): string
    {
        return $this->volume_label . ' (' . $this->volume_units . ')';
    }
}
