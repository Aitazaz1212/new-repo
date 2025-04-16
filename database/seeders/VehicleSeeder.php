<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            [
                'reg' => 'VH001-LON',
                'height' => 2.5,
                'width' => 2.0,
                'depth' => 4.5,
                'weight' => 3500.00,
                'description' => 'Standard Delivery Van',
                'name' => 'Van 1',
                'vehicle_type' => 'delivery_van',
                'assigned_device' => 'DEV-001',
                'max_speed' => '70',
                'driving_time_correction_factor' => '1.2',
                'cost_per_mile' => '0.45',
                'vehicle_activation_cost' => '25.00',
                'cost_per_order' => '5.00',
                'capacity_weight' => '3000',
                'run_distance_limit' => '200',
                'distribution_centre_id' => 1,
                'driver_id' => 1,
                'external_id' => 'EXT-001',
                'territories' => 'North London',
                'manufacturer_info' => 'Mercedes-Benz Sprinter',
                'vin' => 'WDB9061331N123456',
                'color' => 'White',
                'volume' => 15.5
            ],
            [
                'reg' => 'VH002-LON',
                'height' => 2.8,
                'width' => 2.2,
                'depth' => 5.0,
                'weight' => 4500.00,
                'description' => 'Large Delivery Van',
                'name' => 'Van 2',
                'vehicle_type' => 'large_van',
                'assigned_device' => 'DEV-002',
                'max_speed' => '65',
                'driving_time_correction_factor' => '1.3',
                'cost_per_mile' => '0.55',
                'vehicle_activation_cost' => '30.00',
                'cost_per_order' => '6.00',
                'capacity_weight' => '4000',
                'run_distance_limit' => '180',
                'distribution_centre_id' => 1,
                'driver_id' => 2,
                'external_id' => 'EXT-002',
                'territories' => 'South London',
                'manufacturer_info' => 'Ford Transit',
                'vin' => 'WF0XXXTTGN123456',
                'color' => 'Blue',
                'volume' => 18.5
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::create($vehicle);
        }
    }
}
