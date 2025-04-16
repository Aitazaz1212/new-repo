<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTrackingWidget extends Model
{
    protected $fillable = [
        'widgetDomain',
        'widgetLocation',
        'rateDelivery'
    ];

    protected $casts = [
        'rateDelivery' => 'integer'
    ];

    /**
     * Scope a query to find widgets by domain.
     */
    public function scopeForDomain($query, string $domain)
    {
        return $query->where('widgetDomain', $domain);
    }

    /**
     * Scope a query to find widgets by delivery rate.
     */
    public function scopeByDeliveryRate($query, int $rate)
    {
        return $query->where('rateDelivery', $rate);
    }

    /**
     * Get the full widget URL.
     */
    public function getFullUrl(): string
    {
        return "https://{$this->widgetDomain}{$this->widgetLocation}";
    }

    /**
     * Validate a domain format.
     */
    public static function isValidDomain(string $domain): bool
    {
        return strlen($domain) <= 22 && 
               filter_var("http://{$domain}", FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Create a new widget with validation.
     *
     * @throws \InvalidArgumentException
     */
    public static function createWidget(
        string $domain,
        string $location,
        int $rateDelivery
    ): self {
        if (!self::isValidDomain($domain)) {
            throw new \InvalidArgumentException('Invalid domain format');
        }

        return static::create([
            'widgetDomain' => $domain,
            'widgetLocation' => $location,
            'rateDelivery' => $rateDelivery
        ]);
    }
} 