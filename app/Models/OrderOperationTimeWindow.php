<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class OrderOperationTimeWindow extends Model
{
    protected $table = 'order_opertion_time_windows';

    protected $fillable = [
        'order_id',
        'date',
        'start_time',
        'end_time'
    ];

    protected $casts = [
        'order_id' => 'integer',
    ];

    /**
     * Get the order that owns the time window.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope a query to find time windows by order.
     */
    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope a query to find time windows by date.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('date', $date);
    }

    /**
     * Get formatted start time.
     */
    public function getStartTime(?string $format = 'H:i'): ?string
    {
        return $this->start_time ? Carbon::parse($this->start_time)->format($format) : null;
    }

    /**
     * Get formatted end time.
     */
    public function getEndTime(?string $format = 'H:i'): ?string
    {
        return $this->end_time ? Carbon::parse($this->end_time)->format($format) : null;
    }

    /**
     * Get duration in minutes.
     */
    public function getDurationInMinutes(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $end->diffInMinutes($start);
    }

    /**
     * Check if time is within window.
     */
    public function isTimeWithinWindow(string $time): bool
    {
        if (!$this->start_time || !$this->end_time) {
            return false;
        }

        $checkTime = Carbon::parse($time);
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $checkTime->between($start, $end);
    }

    /**
     * Check if window is active.
     */
    public function isActive(): bool
    {
        $now = Carbon::now();
        return $this->date === $now->format('Y-m-d') && $this->isTimeWithinWindow($now->format('H:i:s'));
    }

    /**
     * Get time window summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'date' => $this->date,
            'start_time' => $this->getStartTime(),
            'end_time' => $this->getEndTime(),
            'duration_minutes' => $this->getDurationInMinutes(),
            'is_active' => $this->isActive()
        ];
    }

    /**
     * Get all time windows for an order on a specific date.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getOrderWindowsForDate(int $orderId, string $date): array
    {
        return static::forOrder($orderId)
            ->forDate($date)
            ->get()
            ->map(fn($window) => $window->getSummary())
            ->toArray();
    }

    /**
     * Check if order has any active time windows.
     */
    public static function hasActiveWindows(int $orderId): bool
    {
        $today = Carbon::now()->format('Y-m-d');
        return static::forOrder($orderId)
            ->forDate($today)
            ->get()
            ->contains(fn($window) => $window->isActive());
    }
}
