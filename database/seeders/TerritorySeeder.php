<?php

namespace Database\Seeders;

use App\Models\Territory;
use Illuminate\Database\Seeder;

class TerritorySeeder extends Seeder
{
    public function run(): void
    {
        $territories = [
            [
                'name' => 'Camden Zone',
                'ref_no' => 'NL-CAM-001',
                'coordinates' => json_encode([
                    ['lat' => 51.5390, 'lng' => -0.1426],
                    ['lat' => 51.5500, 'lng' => -0.1400],
                    ['lat' => 51.5450, 'lng' => -0.1350],
                ]),
                'radius' => json_encode(['value' => 5, 'unit' => 'km']),
                'warehouse_id' => '1',
                'color' => '#FF5733',
                'group_id' => '1',
            ],
            [
                'name' => 'Brixton Zone',
                'ref_no' => 'SL-BRX-001',
                'coordinates' => json_encode([
                    ['lat' => 51.4613, 'lng' => -0.1156],
                    ['lat' => 51.4700, 'lng' => -0.1200],
                    ['lat' => 51.4650, 'lng' => -0.1100],
                ]),
                'radius' => json_encode(['value' => 4, 'unit' => 'km']),
                'warehouse_id' => '1',
                'color' => '#33FF57',
                'group_id' => '2',
            ],
        ];

        foreach ($territories as $territory) {
            Territory::create($territory);
        }
    }
}
