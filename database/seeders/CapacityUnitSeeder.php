<?php

namespace Database\Seeders;

use App\Models\CapacityUnit;
use Illuminate\Database\Seeder;

class CapacityUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'capacity_unit' => 'kg',
                'capacity_decimal_precision' => '2',
                'capacity_label' => 'Weight',
                'volume_units' => 'm³',
                'volume_label' => 'Volume',
                'volume_decimal_precision' => '2',
                'enable_capacity_constraint_one' => true,
                'enable_capacity_constraint_two' => false
            ],
            [
                'capacity_unit' => 'pallets',
                'capacity_decimal_precision' => '0',
                'capacity_label' => 'Pallet Spaces',
                'volume_units' => 'boxes',
                'volume_label' => 'Box Count',
                'volume_decimal_precision' => '0',
                'enable_capacity_constraint_one' => true,
                'enable_capacity_constraint_two' => true
            ]
        ];

        foreach ($units as $unit) {
            CapacityUnit::create($unit);
        }
    }
}
