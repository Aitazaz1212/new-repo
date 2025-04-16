<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispTypeMapping extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'disptype_id',
        'name'
    ];

    protected $casts = [
        'disptype_id' => 'integer'
    ];

    public function dispType(): BelongsTo
    {
        return $this->belongsTo(DispType::class, 'disptype_id');
    }
} 