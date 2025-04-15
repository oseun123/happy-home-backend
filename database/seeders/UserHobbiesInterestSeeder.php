<?php

namespace Database\Seeders;

use App\Models\UserHobbiesInterest;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserHobbiesInterestSeeder extends Seeder
{
    public function run()
    {
        UserHobbiesInterest::create([
            'user_id' => 1,
            'hobbies' => ['Reading', 'Traveling', 'Cooking'],
            'interest' => ['Technology', 'Health & Fitness'],
            'language_spoken' => ["Yoruba", "Igbo", "Hausa", "Other"],
        ]);

        UserHobbiesInterest::create([
            'user_id' => 2,
            'hobbies' => ['Gaming', 'Cycling', 'Photography'],
            'interest' => ['Sports', 'Music'],
            'language_spoken' => ["Yoruba", "Igbo", "Hausa", "Other"],
        ]);

        UserHobbiesInterest::create([
            'user_id' => 3,
            'hobbies' => ['Dancing', 'Drawing'],
            'interest' => ['Art & Design', 'Movies & TV Shows'],
            'language_spoken' => ["Yoruba", "Igbo", "Hausa", "Other"],
        ]);
    }
}
