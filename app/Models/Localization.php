<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localization extends Model
{
    protected $table = 'localization';

    public $incrementing = false;

    protected $fillable = [
        'currency',
        'distance_units'
    ];

    // Common currency constants
    public const CURRENCY_USD = 'USD';
    public const CURRENCY_EUR = 'EUR';
    public const CURRENCY_GBP = 'GBP';

    // Common distance unit constants
    public const UNITS_MILES = 'miles';
    public const UNITS_KILOMETERS = 'kilometers';
    public const UNITS_METERS = 'meters';

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = 1;
            }
        });
    }
} 