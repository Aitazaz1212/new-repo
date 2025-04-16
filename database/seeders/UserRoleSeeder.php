<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting to assign roles to users...');

        // Get all users and roles
        $users = User::all();
        $roles = Role::all();

        if ($users->isEmpty()) {
            $this->command->warn('⚠️ No users found in database!');
            return;
        }

        if ($roles->isEmpty()) {
            $this->command->warn('⚠️ No roles found in database! Running RolesSeeder first...');
            $this->call(RolesSeeder::class);
            $roles = Role::all();
        }

        // Assign all roles to each user
        foreach ($users as $user) {
            $existingRoles = $user->roles()->pluck('roles.id')->toArray();
            
            foreach ($roles as $role) {
                // Skip if user already has this role
                if (in_array($role->id, $existingRoles)) {
                    $this->command->info("👥 User {$user->email} already has role: {$role->name}");
                    continue;
                }

                $user->roles()->attach($role->id);
                $this->command->info("✅ Assigned {$role->name} role to user: {$user->email}");
            }
        }

        $this->command->info('🎉 Successfully assigned roles to all users!');
    }
}
