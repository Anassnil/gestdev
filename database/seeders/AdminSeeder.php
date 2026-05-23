<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('AI_DEFAULT_ADMIN_EMAIL', 'admin@example.com');
        $password = env('AI_DEFAULT_ADMIN_PASSWORD', 'password');

        if (! User::where('email', $email)->exists()) {
            User::factory()->create([
                'name' => 'AI Admin',
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        }

        // set AI_ADMINS env fallback by instructing user to add it to .env
    }
}
