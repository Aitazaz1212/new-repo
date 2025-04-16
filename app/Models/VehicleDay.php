<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VehicleDay extends Model
{
    protected $table = 'vehicleDays';

    public $timestamps = false;

    protected $fillable = [
        'reg',
        'day'
    ];

    /**
     * Get all days for a specific vehicle registration.
     *
     * @return array<string>
     */
    public static function getDaysForVehicle(string $reg): array
    {
        return static::where('reg', $reg)
            ->pluck('day')
            ->toArray();
    }

    /**
     * Get all vehicles for a specific day.
     *
     * @return array<string>
     */
    public static function getVehiclesForDay(string $day): array
    {
        return static::where('day', $day)
            ->pluck('reg')
            ->toArray();
    }

    /**
     * Check if a vehicle is scheduled for a specific day.
     */
    public static function isVehicleScheduled(string $reg, string $day): bool
    {
        return static::where('reg', $reg)
            ->where('day', $day)
            ->exists();
    }

    /**
     * Schedule a vehicle for multiple days.
     *
     * @param array<string> $days
     */
    public static function scheduleVehicle(string $reg, array $days): void
    {
        $data = array_map(function($day) use ($reg) {
            return [
                'reg' => $reg,
                'day' => $day
            ];
        }, $days);

        static::insert($data);
    }

    /**
     * Remove all scheduled days for a vehicle.
     */
    public static function clearVehicleSchedule(string $reg): int
    {
        return static::where('reg', $reg)->delete();
    }

    /**
     * Remove specific days from a vehicle's schedule.
     *
     * @param array<string> $days
     */
    public static function removeVehicleDays(string $reg, array $days): int
    {
        return static::where('reg', $reg)
            ->whereIn('day', $days)
            ->delete();
    }

    /**
     * Get vehicles scheduled for multiple days.
     *
     * @param array<string> $days
     * @return Collection<string>
     */
    public static function getVehiclesForDays(array $days): Collection
    {
        return static::whereIn('day', $days)
            ->pluck('reg')
            ->unique();
    }

    /**
     * Get schedule conflicts for a vehicle.
     *
     * @param array<string> $days
     * @return array<string>
     */
    public static function getScheduleConflicts(string $reg, array $days): array
    {
        return static::where('reg', $reg)
            ->whereIn('day', $days)
            ->pluck('day')
            ->toArray();
    }

    /**
     * Scope a query to find schedules by day.
     */
    public function scopeForDay(Builder $query, string $day): Builder
    {
        return $query->where('day', $day);
    }

    /**
     * Scope a query to find schedules by vehicle registration.
     */
    public function scopeForVehicle(Builder $query, string $reg): Builder
    {
        return $query->where('reg', $reg);
    }

    /**
     * Get schedule summary for a vehicle.
     *
     * @return array<string, array<string>>
     */
    public static function getVehicleScheduleSummary(string $reg): array
    {
        $days = static::getDaysForVehicle($reg);
        
        return [
            'registration' => $reg,
            'scheduled_days' => $days
        ];
    }

    /**
     * Update vehicle schedule.
     *
     * @param array<string> $days
     */
    public static function updateVehicleSchedule(string $reg, array $days): void
    {
        static::clearVehicleSchedule($reg);
        static::scheduleVehicle($reg, $days);
    }
}