<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchTypes extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'type',
        'company_id'
    ];

    protected $casts = [
        'company_id' => 'integer'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
