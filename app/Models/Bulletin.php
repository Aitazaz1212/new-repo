<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bulletin extends Model
{
    protected $table = 'bulletin';

    protected $fillable = [
        'expires',
        'message',
        'staff_id',
        'title',
        'deleted'
    ];

    protected $casts = [
        'expires' => 'datetime',
        'deleted' => 'boolean'
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
