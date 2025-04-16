<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bulletin2 extends Model
{
    use SoftDeletes;

    protected $table = 'bulletins';

    protected $fillable = [
        'expires',
        'user_id',
        'message',
        'title'
    ];

    protected $casts = [
        'expires' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 