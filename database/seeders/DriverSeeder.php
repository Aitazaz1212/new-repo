<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = [
            [
                'name' => 'John Smith',
                'uName' => 'JohnSmith',
                'external_id' => 'DRV-001',
                'pwd' => Hash::make('password123'),
                'distribution_centre' => 1,
                'num' => 1001,
                'comment' => 'Experienced driver - North London area',
                'vehicle' => 1,
                'cost_per_hour' => '15.50',
                'alerts_for_performers' => true,
                'territories' => json_encode(['North London', 'Central London']),
                'start_of_day_location' => 'warehouse',
                'end_of_day_location' => 'warehouse',
                'driving_limit' => '9',
                'duty_time_limit' => '10',
                'run_duration_limit' => '12',
                'start_of_day_address' => 'Main Distribution Center, London',
                'end_of_day_address' => 'Main Distribution Center, London'
            ],
            [
                'name' => 'Sarah Johnson',
                'uName' => 'SarahJohnson',
                'external_id' => 'DRV-002',
                'pwd' => Hash::make('password123'),
                'distribution_centre' => 1,
                'num' => 1002,
                'comment' => 'Senior driver - South London specialist',
                'vehicle' => 2,
                'cost_per_hour' => '16.50',
                'alerts_for_performers' => true,
                'territories' => json_encode(['South London', 'Central London']),
                'start_of_day_location' => 'warehouse',
                'end_of_day_location' => 'warehouse',
                'driving_limit' => '9',
                'duty_time_limit' => '10',
                'run_duration_limit' => '12',
                'start_of_day_address' => 'Main Distribution Center, London',
                'end_of_day_address' => 'Main Distribution Center, London'
            ],
        ];

        foreach ($drivers as $driver) {
            Driver::create($driver);
        }
    }
}
