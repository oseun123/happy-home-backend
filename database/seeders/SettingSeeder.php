<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run()
    {
        Setting::create([
            'key' => 'height_range',
            'value' => ["4'0-5'0", "5'1-6'0", "6'1-7'0"],
        ]);

        Setting::create([
            'key' => 'weight_range',
            'value' => ["40-50kg", "51-60kg", "61-70kg"],
        ]);

        Setting::create([
            'key' => 'ethnicity',
            'value' => ["Yoruba", "Igbo", "Hausa", "Other"],
        ]);

        Setting::create([
            "key" => "marital_status",
            "value" => ["Single", "Married", "Divorced"]
        ]);
    }
}
