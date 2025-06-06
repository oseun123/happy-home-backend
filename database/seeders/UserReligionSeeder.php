<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserBioData;
use App\Models\UserReligion;

class UserReligionSeeder extends Seeder
{
    public function run()
    {
        // Example religions and denominations for seeding
        $religionsAndDenominations = [
            'Christianity' => [
                'Catholic',
                'Protestant',
                'Orthodox',
                'Anglican',
                'Baptist',
                'Methodist',
                'Pentecostal',
            ],
            'Islam' => [
                'Sunni',
                'Shia',
                'Sufism',
            ],
        ];

        // Loop through all the UserBioData records and assign religions
        $userBioDataRecords = UserBioData::all();

        foreach ($userBioDataRecords as $bioData) {
            foreach ($religionsAndDenominations as $religion => $denominations) {
                foreach ($denominations as $denomination) {
                    UserReligion::create([
                        'user_bio_data_id' => $bioData->id, // Associate with the bio_data record
                        'religion' => $religion,
                        'denomination' => $denomination,
                    ]);
                }
            }
        }
    }
}
