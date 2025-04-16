<?php

namespace Database\Seeders;

use App\Models\TerritoriesGroup;
use Illuminate\Database\Seeder;

class TerritoriesGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name' => 'North London',
                'warehouse_id' => 1,
            ],
            [
                'name' => 'South London',
                'warehouse_id' => 1,
            ],
            // Add more groups as needed
        ];

        foreach ($groups as $group) {
            TerritoriesGroup::create($group);
        }
    }
}
