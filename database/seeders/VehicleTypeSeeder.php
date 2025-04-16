<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Small Van',
                'ref_no' => 'VT-SMALL',
                'working_time_before_break' => '4',
                'routing_mode' => 'car',
                'avoid_urban_areas' => false,
                'avoid_london_ultra_low_emission_zone' => true,
                'weight' => 2000.00,
                'height' => 2.2,
                'width' => 1.8,
                'length' => 4.5,
                'axle_load' => 1800.00
            ],
            [
                'name' => 'Large Van',
                'ref_no' => 'VT-LARGE',
                'working_time_before_break' => '4',
                'routing_mode' => 'truck',
                'avoid_urban_areas' => true,
                'avoid_london_ultra_low_emission_zone' => true,
                'weight' => 3500.00,
                'height' => 2.8,
                'width' => 2.2,
                'length' => 6.0,
                'axle_load' => 3000.00
            ]
        ];

        foreach ($types as $type) {
            VehicleType::create($type);
        }
    }
}
