<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'email' => 'user1@example.com',
            'password' => Hash::make('password123'),
            'verification_token' => Str::random(6),
            'is_verified' => true,
        ]);

        User::create([
            'email' => 'user2@example.com',
            'password' => Hash::make('password123'),
            'verification_token' => Str::random(6),
            'is_verified' => true,
        ]);

        User::create([
            'email' => 'user3@example.com',
            'password' => Hash::make('password123'),
            'verification_token' => Str::random(6),
            'is_verified' => true,
        ]);
    }
}
