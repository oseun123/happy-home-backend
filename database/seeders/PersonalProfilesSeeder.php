<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\User;

class PersonalProfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Generate 10 personal profiles for existing users
        User::all()->each(function ($user) use ($faker) {
            DB::table('personal_profiles')->insert([
                'user_id'        => $user->id,
                'first_name'     => $faker->firstName,
                'last_name'      => $faker->lastName,
                'middle_name'    => $faker->optional()->firstName,
                'date_of_birth'  => $faker->date(),
                'phone_number'   => $faker->unique()->phoneNumber,
                'photo'          => $faker->imageUrl(400, 400, 'people'),
                'gender'         => $faker->randomElement(['Male', 'Female']),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });
    }
}
