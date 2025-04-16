<?php

namespace Database\Seeders;

use App\Models\OperationDuration;
use Illuminate\Database\Seeder;

class OperationDurationSeeder extends Seeder
{
    public function run(): void
    {
        $durations = [
            [
                'customer_id' => 1,
                'fixed_loading_duration' => '15',
                'variable_loading_duration_per_unit' => '2',
                'fixed_time_per_address' => json_encode(['min' => 5, 'max' => 10]),
                'fixed_time_per_order' => json_encode(['min' => 10, 'max' => 20]),
                'variable_time_per_capacity_delivery' => json_encode(['rate' => 1.5]),
                'variable_time_per_capacity_collection' => json_encode(['rate' => 2.0]),
                'fixed_un_loading_duration' => '10',
                'variable_un_loading_duration_per_unit' => '1.5'
            ],
            [
                'customer_id' => 2,
                'fixed_loading_duration' => '20',
                'variable_loading_duration_per_unit' => '2.5',
                'fixed_time_per_address' => json_encode(['min' => 8, 'max' => 15]),
                'fixed_time_per_order' => json_encode(['min' => 15, 'max' => 25]),
                'variable_time_per_capacity_delivery' => json_encode(['rate' => 2.0]),
                'variable_time_per_capacity_collection' => json_encode(['rate' => 2.5]),
                'fixed_un_loading_duration' => '12',
                'variable_un_loading_duration_per_unit' => '2.0'
            ]
        ];

        foreach ($durations as $duration) {
            OperationDuration::create($duration);
        }
    }
}
