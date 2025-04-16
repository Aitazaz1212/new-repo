<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Click extends Model
{
    protected $fillable = [
        'camp',
        'link',
        'time',
        'id'
    ];

    protected $casts = [
        'camp' => 'integer',
        'id' => 'integer'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Camp::class, 'camp');
    }
}
