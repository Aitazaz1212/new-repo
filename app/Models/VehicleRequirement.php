<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VehicleRequirement extends Model
{
    protected $table = 'vehicle_requirements';

    protected $fillable = [
        'name',
        'ref_no',
        'working_time_before_break',
        'incompatible_vehicle_requirements'
    ];

    protected $casts = [
        'incompatible_vehicle_requirements' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the working time before break in minutes.
     */
    public function getWorkingTimeInMinutes(): int
    {
        return (int) $this->working_time_before_break;
    }

    /**
     * Get incompatible requirements as array.
     *
     * @return array<string>
     */
    public function getIncompatibleRequirements(): array
    {
        return is_array($this->incompatible_vehicle_requirements)
            ? $this->incompatible_vehicle_requirements
            : json_decode($this->incompatible_vehicle_requirements ?? '[]', true);
    }

    /**
     * Check if requirement is incompatible with another.
     */
    public function isIncompatibleWith(self $requirement): bool
    {
        return in_array(
            $requirement->ref_no,
            $this->getIncompatibleRequirements(),
            true
        );
    }

    /**
     * Add incompatible requirement.
     */
    public function addIncompatibleRequirement(string $refNo): void
    {
        $incompatible = $this->getIncompatibleRequirements();
        $incompatible[] = $refNo;
        $this->incompatible_vehicle_requirements = array_unique($incompatible);
        $this->save();
    }

    /**
     * Remove incompatible requirement.
     */
    public function removeIncompatibleRequirement(string $refNo): void
    {
        $incompatible = $this->getIncompatibleRequirements();
        $this->incompatible_vehicle_requirements = array_values(
            array_diff($incompatible, [$refNo])
        );
        $this->save();
    }

    /**
     * Scope a query to find by reference number.
     */
    public function scopeByRefNo(Builder $query, string $refNo): Builder
    {
        return $query->where('ref_no', $refNo);
    }

    /**
     * Scope a query to find by name.
     */
    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Get all compatible requirements.
     */
    public function getCompatibleRequirements(): Collection
    {
        $incompatible = $this->getIncompatibleRequirements();
        return self::whereNotIn('ref_no', $incompatible)
            ->where('id', '!=', $this->id)
            ->get();
    }

    /**
     * Get requirement summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ref_no' => $this->ref_no,
            'working_time' => $this->working_time_before_break,
            'incompatible_with' => $this->getIncompatibleRequirements(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Check if requirements are compatible.
     *
     * @param array<self> $requirements
     */
    public static function areCompatible(array $requirements): bool
    {
        foreach ($requirements as $req1) {
            foreach ($requirements as $req2) {
                if ($req1->id !== $req2->id && $req1->isIncompatibleWith($req2)) {
                    return false;
                }
            }
        }
        return true;
    }
}
