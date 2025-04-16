<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'name_of_warehouse' => 'Main Distribution Center',
                'location_of_warehouse' => '123 Logistics Way',
                'longitude' => -0.1276,
                'latitude' => 51.5074,
                'postalCode' => 'SW1A 1AA',
                'ref_number' => 'WH001',
                'address_prefix' => 'UK-LON',
                'phone' => '+44 20 7123 4567',
                'driving_time_correction_factor' => '1.2',
                'daily_driving_limt' => '8',
                'duty_time_limt' => '10',
                'run_duration_limt' => '12',
                'collection_after_deliveries' => 'true',
                'start_fo_day_location' => 'warehouse',
                'end_of_day_location' => 'warehouse',
                'sunday' => json_encode(['closed' => true]),
                'monday' => json_encode(['open' => '09:00', 'close' => '17:00']),
                'tuesday' => json_encode(['open' => '09:00', 'close' => '17:00']),
                'wednesday' => json_encode(['open' => '09:00', 'close' => '17:00']),
                'thursday' => json_encode(['open' => '09:00', 'close' => '17:00']),
                'friday' => json_encode(['open' => '09:00', 'close' => '17:00']),
                'saturday' => json_encode(['open' => '10:00', 'close' => '14:00']),
            ],
            // Add more warehouses as needed
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
    }
}
