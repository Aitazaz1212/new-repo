<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockDay extends Model
{
    protected $table = 'blockDays';

    protected $fillable = [
        'date',
        'saturday',
        'user_id'
    ];

    protected $casts = [
        'saturday' => 'date'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
