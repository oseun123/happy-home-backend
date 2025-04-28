<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Models\PersonalProfile;
use Illuminate\Support\Facades\Http;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\DB;

class PersonalProfileController extends Controller
{

    public function index()
    {
        $profiles = PersonalProfile::with('user')->get();

        if ($profiles->isEmpty()) {
            return ResponseHelper::withError('No personal profiles found.');
        }

        return ResponseHelper::withSuccess('Personal profiles retrieved successfully.', $profiles);
    }

    public function show(User $user)
    {
        $profile = $user->personalProfile;

        if (!$profile) {
            return ResponseHelper::withError('Personal profile not found.');
        }

        return ResponseHelper::withSuccess('Personal profile retrieved successfully.', $profile);
    }

    public function store(Request $request, User $user)
    {

        // dd(config('cloudinary.cloud_url'));

        $request->validate([
            'phone_number' => 'required|string|unique:personal_profiles,phone_number',
            'photo' => 'required|file|image|max:5120', // Max 5MB image file
        ]);

        // Ensure user does not already have a personal profile
        if ($user->personalProfile) {
            return ResponseHelper::withError('User already has a personal profile.');
        }
        DB::beginTransaction(); // Start transaction

        try {
            // Upload the file to Cloudinary
            $folder = env('APP_NAME', 'Ayanfe') . '/personal_profiles';

            // return $folder;
            $uploadedImage = Cloudinary::upload($request->file('photo')->getRealPath(), [
                'folder' => $folder,
                'resource_type' => 'image'
            ]);

            $photoUrl = $uploadedImage->getSecurePath();

            // Fetch and store user details from Dojah
            $profile = $this->fetchAndStorePersonalProfile($request->phone_number, $user, $photoUrl);

            if (!$profile) {
                throw new \Exception('Failed to create personal profile.');
            }
            // set user default settings
            $this->userSetting($user);
            DB::commit(); // Save all changes

            return ResponseHelper::withSuccess('Personal profile created successfully.', $profile);
        } catch (\Exception $e) {
            DB::rollBack(); // Undo everything if something fails
            return ResponseHelper::withError('Error: ' . $e->getMessage());
        }
    }

    private function fetchAndStorePersonalProfile($phone_number, $user, $photoUrl)
    {
        // dd(env('DOJAH_VERIFY_PHONE_URL'), env('DOJAH_APP_ID'), env('DOJAH_SECRET_KEY'));
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'AppId' => env('DOJAH_APP_ID'),
            'Authorization' => env('DOJAH_SECRET_KEY'),
        ])->get(env('DOJAH_VERIFY_PHONE_URL'), [
            'phone_number' => $phone_number,
            'country_code' => 'NG', // Change as needed
        ]);


        $data = $response->json();
        // dd($data, isset($data['entity']));

        if (!isset($data['entity'])) {
            return ResponseHelper::withError('Phone Verification fails.'); // Returning null instead of error response
        }

        return PersonalProfile::create([
            'user_id' => $user->id,
            'first_name' => $data['entity']['first_name'] ?? null,
            'last_name' => $data['entity']['last_name'] ?? null,
            'middle_name' => $data['entity']['middle_name'] ?? null,
            'date_of_birth' => $data['entity']['date_of_birth'] ?? null,
            'phone_number' => $data['entity']['phone_number'],
            'gender' => $data['entity']['gender'] ?? null,
            'photo' => $photoUrl, // Save photo URL
        ]);
    }


    // private function fetchAndStorePersonalProfile($phone_number, $user, $photoUrl)
    // {
    //     $faker = \Faker\Factory::create();

    //     // Generate fake user details
    //     $fakeData = [
    //         'first_name' => $faker->firstName(),
    //         'last_name' => $faker->lastName(),
    //         'middle_name' => $faker->optional()->firstName(),
    //         'date_of_birth' => $faker->date('Y-m-d', '-20 years'), // Random DOB, at least 20 years old
    //         'phone_number' => $phone_number,
    //         'gender' => $faker->randomElement(['M', 'F', 'MF']),
    //     ];

    //     // Store in the database
    //     return PersonalProfile::create([
    //         'user_id' => $user->id,
    //         'first_name' => $fakeData['first_name'],
    //         'last_name' => $fakeData['last_name'],
    //         'middle_name' => $fakeData['middle_name'],
    //         'date_of_birth' => $fakeData['date_of_birth'],
    //         'phone_number' => $fakeData['phone_number'],
    //         'gender' => $fakeData['gender'],
    //         'photo' => $photoUrl, // Store the uploaded photo URL
    //     ]);
    // }


    private function userSetting($user)
    {

        $user->settings()->create([
            'hide_age' => false,
            'hide_location' => false,
            'matchup_settings' => 30,
            'notify_me' => true,
            'deactive_account' => false,
            'short_bio' => null,
            'cover_photo' => null,
            'photo_1' => null,
            'photo_2' => null,
            'photo_3' => null,
            'photo_4' => null,
        ]);
    }
}
