<?php

namespace Database\Seeders;

use App\Models\UserBioData;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserBioDataSeeder extends Seeder
{
    public function run()
    {
        UserBioData::create([
            'user_id' => 1,
            'height_range' => "4'0-5'0",
            'weight_range' => "40-50kg",
            'ethnicity' => 'Yoruba',
            'genotype' => 'AA',
            'marital_status' => 'Single',
            'occupation' => 'Engineer',

        ]);

        UserBioData::create([
            'user_id' => 2,
            'height_range' => "5'1-6'0",
            'weight_range' => "51-60kg",
            'ethnicity' => 'Igbo',
            'genotype' => 'AS',
            'marital_status' => 'Married',
            'occupation' => 'Doctor',

        ]);

        UserBioData::create([
            'user_id' => 3,
            'height_range' => "6'1-7'0",
            'weight_range' => "61-70kg",
            'ethnicity' => 'Hausa',
            'genotype' => 'SS',
            'marital_status' => 'Divorced',
            'occupation' => 'Lawyer',

        ]);
    }
}
