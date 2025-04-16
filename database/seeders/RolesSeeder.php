<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
            ],
            [
                'name' => 'Call Staff',
                'slug' => 'call_staff',
            ],
            [
                'name' => 'Deliveries Manager',
                'slug' => 'deliveries_manager',
            ],
        ];

        foreach ($roles as $role) {
            $this->command->info('⏳ Creating role: ' . $role['name']);
            Role::create($role);
        }
        $this->command->info('✅ All roles created successfully');
    }
}
