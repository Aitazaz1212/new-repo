<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Direct extends Model
{
    protected $table = 'direct';

    public $incrementing = false;

    protected $fillable = [
        'oid',
        'name',
        'number',
        'street',
        'town',
        'postcode',
        'tel',
        'mob',
        'email'
    ];

    protected $casts = [
        'oid' => 'integer'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'oid');
    }
}
