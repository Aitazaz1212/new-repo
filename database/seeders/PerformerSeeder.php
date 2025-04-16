<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class PerformerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = [
            [
                'name' => 'Performer',
                'slug' => 'performer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Role::insert($role);

        $this->command->info('✅ Performer role created successfully');
    }
}
