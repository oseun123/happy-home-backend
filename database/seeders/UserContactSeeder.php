<?php

namespace Database\Seeders;

use App\Models\UserContact;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserContactSeeder extends Seeder
{
    public function run()
    {
        UserContact::create([
            'user_id' => 1,
            'house_number' => '123',
            'street_name' => 'Main Street',
            'land_mark' => 'Near the park',
            'lga' => 'Lagos Mainland',
            'postal_code' => '10001',
            'state' => 'Lagos',
            'country' => 'Nigeria',
        ]);

        UserContact::create([
            'user_id' => 2,
            'house_number' => '456',
            'street_name' => 'Avenue Road',
            'land_mark' => 'Opposite the mall',
            'lga' => 'Central Abuja',
            'postal_code' => '20002',
            'state' => 'Abuja',
            'country' => 'Nigeria',
        ]);

        UserContact::create([
            'user_id' => 3,
            'house_number' => '789',
            'street_name' => 'Hilltop Road',
            'land_mark' => 'Beside the school',
            'lga' => 'Kaduna South',
            'postal_code' => '30003',
            'state' => 'Kaduna',
            'country' => 'Nigeria',
        ]);
    }
}
