<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;

class CreateUser extends Command
{
    protected $signature = 'users:create';
    protected $description = 'Create a new user interactively 👤';

    public function handle()
    {
        $this->info('🚀 Starting user creation wizard...');

        $name = $this->askValid(
            '👤 Enter name',
            'name',
            ['required', 'string', 'max:255']
        );

        $email = $this->askValid(
            '📧 Enter user email',
            'email',
            ['required', 'email', 'unique:users']
        );

        $password = $this->secret('🔐 Enter password (min 8 characters)');

        if (strlen($password) < 8) {
            $this->error('❌ Password must be at least 8 characters long');
            return Command::FAILURE;
        }

        $this->info('⏳ Creating user...');

        try {
            $user = User::create([
                'name' => $name,
                'username' => explode('@', $email)[0],
                'email' => $email,
                'password' => Hash::make($password),
                'language' => 'en',
            ]);

            $this->info('⏳ Attaching roles to user...');

            $user->roles()->attach(Role::all());

            $this->info("✅ User created successfully with ID: {$user->id}");

            $this->table(
                ['👤 Name', '📧 Email', '👤 Username'],
                [[$user->first_name . ' ' . $user->last_name, $user->email, $user->username]]
            );

            $this->info('👤 User roles: ' . $user->roles()->pluck('name')->implode(', '));

            $this->info('🎉 All done! You can now login.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to create user: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function askValid(string $question, string $field, array $rules)
    {
        $value = $this->ask($question);

        $validator = Validator::make(
            [$field => $value],
            [$field => $rules]
        );

        if ($validator->fails()) {
            $this->error('❌ ' . $validator->errors()->first($field));
            return $this->askValid($question, $field, $rules);
        }

        return $value;
    }
}
