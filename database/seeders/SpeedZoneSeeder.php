<?php

namespace Database\Seeders;

use App\Models\SpeedZone;
use Illuminate\Database\Seeder;

class SpeedZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'name' => 'Central London',
                'coordinates' => json_encode([
                    ['lat' => 51.5074, 'lng' => -0.1278],
                    ['lat' => 51.5124, 'lng' => -0.1258],
                    ['lat' => 51.5104, 'lng' => -0.1228]
                ]),
                'radius' => '5.0',
                'speed_correction_error' => '0.8',
                'color' => '#FF0000'
            ],
            [
                'name' => 'Suburban Area',
                'coordinates' => json_encode([
                    ['lat' => 51.5524, 'lng' => -0.2278],
                    ['lat' => 51.5624, 'lng' => -0.2258],
                    ['lat' => 51.5604, 'lng' => -0.2228]
                ]),
                'radius' => '8.0',
                'speed_correction_error' => '0.9',
                'color' => '#00FF00'
            ]
        ];

        foreach ($zones as $zone) {
            SpeedZone::create($zone);
        }
    }
}
