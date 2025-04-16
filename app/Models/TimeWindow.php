<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeWindow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'data',
        'from',
        'to'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'data' => 'array' // If data is stored as JSON
    ];

    /**
     * Get formatted time window
     *
     * @return string
     */
    public function getFormattedTimeWindowAttribute(): string
    {
        return "{$this->from} - {$this->to}";
    }

    /**
     * Check if a given time falls within this window
     *
     * @param string $time
     * @return bool
     */
    public function isTimeWithinWindow(string $time): bool
    {
        return strtotime($time) >= strtotime($this->from) && 
               strtotime($time) <= strtotime($this->to);
    }
} 