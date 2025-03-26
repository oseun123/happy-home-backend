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
            ['email' => 'superadmin@example.com'],
            [
                'password' => Hash::make('password123'), // Change this in production
            ]
        );
    }
}
