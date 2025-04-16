<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Muhammad Saqib',
            'username' => 'muhammad.saqib',
            'email' => 'muhammad.saqib@focusitservices.co.uk',
            'password' => Hash::make('11223344'),
            'language' => 'en',
        ]);
        
        User::create([
            'name' => 'Muhammad Ali',
            'username' => 'muhammad.ali',
            'email' => 'muhammad.ali@focusitservices.co.uk',
            'password' => Hash::make('123456789*aA'),
            'language' => 'en',
        ]);

        User::create([
            'name' => 'Waleed Mughal',
            'username' => 'waleed.mughal',
            'email' => 'waleed.mughal@focusitservices.co.uk',
            'password' => Hash::make('waleed1234@'),
            'language' => 'en',
        ]);

        User::create([
            'name' => 'Usama Khan',
            'username' => 'usama.khan',
            'email' => 'usama.khan@focusitservices.co.uk',
            'password' => Hash::make('123456789*aA'),
            'language' => 'en',
        ]);

        User::create([
            'name' => 'Attaullah Khan',
            'username' => 'attaullah.khan',
            'email' => 'attaullah.khan@focusitservices.co.uk',
            'password' => Hash::make('123456789*aA'),
            'language' => 'en',
        ]);

        User::create([
            'name' => 'Muhammad Talha',
            'username' => 'muhammad.talha',
            'email' => 'muhammad.talha@focusitservices.co.uk',
            'password' => Hash::make('123456789*aA'),
            'language' => 'en',
        ]);

        User::create([
            'name' => 'Mohsin Ali',
            'username' => 'mohsin.ali',
            'email' => 'mohsin.ali@focusitservices.co.uk',
            'password' => Hash::make('123456789*aA'),
            'language' => 'en',
        ]);

        User::create([
            'name' => 'Adeel',
            'username' => 'a.hashmi',
            'email' => 'a.hashmi@focusitservices.co.uk',
            'password' => Hash::make('123456789*aA'),
            'language' => 'en',
        ]);


        $this->command->info('✅ User created successfully');
    }
}
