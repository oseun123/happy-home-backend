<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserPreferredMatchReligion;
use App\Models\UserPreferredMatch;

class UserPreferredMatchReligionSeeder extends Seeder
{
    public function run()
    {
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
            ]

        ];

        // Loop through each user_preferred_match
        $userPreferredMatches = UserPreferredMatch::all();

        foreach ($userPreferredMatches as $preferredMatch) {
            foreach ($religionsAndDenominations as $religion => $denominations) {
                foreach ($denominations as $denomination) {
                    UserPreferredMatchReligion::create([
                        'user_preferred_match_id' => $preferredMatch->id,
                        'religion' => $religion,
                        'denomination' => $denomination,
                    ]);
                }
            }
        }
    }
}
