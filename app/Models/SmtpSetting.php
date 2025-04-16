<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmtpSetting extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id_company' => 'integer',
        'tls' => 'integer'
    ];

    /**
     * Get the company that owns the SMTP settings.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company');
    }
} 