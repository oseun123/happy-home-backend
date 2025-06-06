<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPreferredMatch;
use Illuminate\Database\Seeder;

class UserPreferredMatchSeeder extends Seeder
{
    public function run()
    {
        // Loop through users and create preferred matches
        $users = User::all();

        foreach ($users as $user) {
            // Assuming you want to create a single preferred match for each user
            UserPreferredMatch::create([
                'user_id' => $user->id,
                'relationship_type' => 'Mariage', // Example relationship_type
                'gender' => ['Male', 'Female'], // Example gender preferences
                'age_range' => ['18-25', '26-30'], // Example age range preferences
                'height_range' => ["4'0 - 4'5"], // Example age range preferences
                'marital_status' => ['Single', 'Divorced'], // Example marital status preferences
                'ethnicity' => ['Caucasian', 'African-American'], // Example ethnicity preferences
                'genotype' => ['AA', 'AS'], // Example genotype preferences
                'nationality' => ['American', 'Nigerian'], // Example nationality preferences
                'state' => ['California', 'New York'], // Example state preferences
                // 'hobbies' => json_encode(['Reading', 'Traveling']), // Example hobbies preferences
                // 'interests' => json_encode(['Technology', 'Music']), // Example interests preferences
            ]);
        }
    }
}
