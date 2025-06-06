<?php

namespace Database\Seeders;

use App\Models\UserSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSettingSeeder extends Seeder
{
    public function run()
    {
        UserSetting::create([
            'user_id' => 1,
            'matchup_settings' => 70,
        ]);

        UserSetting::create([
            'user_id' => 2,
            'matchup_settings' => 75,
        ]);

        UserSetting::create([
            'user_id' => 3,
            'matchup_settings' => 80,
        ]);
    }
}
