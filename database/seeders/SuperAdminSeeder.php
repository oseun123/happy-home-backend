<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\SuperAdmin;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        SuperAdmin::updateOrCreate(
            ['email' => 'tolu@yopmail.com'],
            [
                'password' => Hash::make('enterprise'), // Change this in production
            ]
        );
    }
}
