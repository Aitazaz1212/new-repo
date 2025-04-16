<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanningPreference extends Model
{
    protected $table = 'planning_preferences';

    protected $fillable = [
        'enable_toll_roads',
        'allow_several_runs_for_a_vehicle_per_day',
        'territories_planning_mode',
        'working_time_before_break',
        'enable_on_board_time_limit_for_deliveries',
        'on_board_time_limit_for_deliveries',
        'enable_on_board_time_limit_for_collections',
        'on_board_time_limit_for_collections'
    ];

    /**
     * Check if toll roads are enabled.
     */
    public function isTollRoadsEnabled(): bool
    {
        return $this->enable_toll_roads === 'yes';
    }

    /**
     * Check if multiple runs per vehicle per day are allowed.
     */
    public function isMultipleRunsAllowed(): bool
    {
        return $this->allow_several_runs_for_a_vehicle_per_day === 'yes';
    }

    /**
     * Check if delivery time limit is enabled.
     */
    public function isDeliveryTimeLimitEnabled(): bool
    {
        return $this->enable_on_board_time_limit_for_deliveries === 'yes';
    }

    /**
     * Check if collection time limit is enabled.
     */
    public function isCollectionTimeLimitEnabled(): bool
    {
        return $this->enable_on_board_time_limit_for_collections === 'yes';
    }

    /**
     * Get the working time before break in minutes.
     */
    public function getWorkingTimeBeforeBreak(): ?int
    {
        return $this->working_time_before_break ? (int) $this->working_time_before_break : null;
    }

    /**
     * Get the delivery time limit in minutes.
     */
    public function getDeliveryTimeLimit(): ?int
    {
        return $this->on_board_time_limit_for_deliveries ? 
            (int) $this->on_board_time_limit_for_deliveries : null;
    }

    /**
     * Get the collection time limit in minutes.
     */
    public function getCollectionTimeLimit(): ?int
    {
        return $this->on_board_time_limit_for_collections ? 
            (int) $this->on_board_time_limit_for_collections : null;
    }

    /**
     * Update preference with validation.
     */
    public function updatePreference(string $key, string $value): bool
    {
        if (!in_array($key, $this->fillable)) {
            return false;
        }

        // Validate yes/no fields
        if (str_contains($key, 'enable_') && !in_array($value, ['yes', 'no'])) {
            return false;
        }

        $this->$key = $value;
        return $this->save();
    }

    /**
     * Get all preferences as an array.
     */
    public function getAllPreferences(): array
    {
        return [
            'toll_roads' => $this->isTollRoadsEnabled(),
            'multiple_runs' => $this->isMultipleRunsAllowed(),
            'territories_mode' => $this->territories_planning_mode,
            'working_time_before_break' => $this->getWorkingTimeBeforeBreak(),
            'delivery_time_limit' => [
                'enabled' => $this->isDeliveryTimeLimitEnabled(),
                'limit' => $this->getDeliveryTimeLimit()
            ],
            'collection_time_limit' => [
                'enabled' => $this->isCollectionTimeLimitEnabled(),
                'limit' => $this->getCollectionTimeLimit()
            ]
        ];
    }
} 