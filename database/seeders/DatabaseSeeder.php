<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,
            RolesSeeder::class,
            UserRoleSeeder::class,
            SmsEmailFormatSeeder::class,
            CommunicationLogSeeder::class,
        ]);
    }
}
