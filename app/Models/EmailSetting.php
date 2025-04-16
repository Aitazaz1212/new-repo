<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSetting extends Model
{
    protected $table = 'emailSettings';

    protected $fillable = [
        'email',
        'pwd',
        'smtp',
        'name',
        'id_company',
        'tls',
        'outName',
        'ssl_one',
        'port'
    ];

    protected $casts = [
        'id_company' => 'integer',
        'tls' => 'integer'
    ];

    /**
     * Get the company that owns the email settings.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'id_company');
    }
} 