<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Models\PersonalProfile;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UserSettingController extends Controller
{
    public function store(Request $request, User $user)
    {
        $request->validate([
            'short_bio' => 'nullable|string',
            'hide_age' => 'boolean',
            'hide_location' => 'boolean',
            'matchup_settings' => 'nullable|integer|min:0|max:100',
            'notify_me' => 'boolean',
            'deactive_account' => 'boolean',
        ]);

        if ($user->setting) {
            return ResponseHelper::withError('User settings already exist.');
        }

        $setting = UserSetting::create([
            'user_id' => $user->id,
            'short_bio' => $request->short_bio,
            'hide_age' => $request->hide_age ?? false,
            'hide_location' => $request->hide_location ?? false,
            'matchup_settings' => $request->matchup_settings ?? 5,
            'notify_me' => $request->notify_me ?? true,
            'deactive_account' => $request->deactive_account ?? false,
        ]);

        return ResponseHelper::withSuccess('Settings saved successfully.', $setting);
    }

    public function show(User $user)
    {
        $setting = $user->settings;

        if (!$setting) {
            return ResponseHelper::withError('Settings not found for this user.');
        }

        return ResponseHelper::withSuccess('Settings retrieved successfully.', $setting);
    }

    public function updateField(Request $request, User $user, $field)
    {


        $allowedFields = [
            'short_bio',
            'hide_age',
            'hide_location',
            'matchup_settings',
            'notify_me',
            'deactive_account'
        ];

        if (!in_array($field, $allowedFields)) {
            return ResponseHelper::withError('Invalid settings field.');
        }



        $setting = UserSetting::firstOrCreate(['user_id' => $user->id]);

        $value = $request->value;


        $setting->{$field} = $value;
        $setting->save();

        return ResponseHelper::withSuccess(ucwords(str_replace('_', ' ', $field)) . ' updated successfully.', $setting);
    }


    public function updatePhoto(Request $request, User $user, $field)
    {
        $validPhotoFields = ['cover_photo', 'photo_1', 'photo_2', 'photo_3', 'photo_4', 'photo'];

        if (!in_array($field, $validPhotoFields)) {
            return ResponseHelper::withError('Invalid photo field.');
        }

        if ($field === 'photo') {
            // If the request wants to remove the photo (null it)
            if ($request->has('remove') && filter_var($request->input('remove'), FILTER_VALIDATE_BOOLEAN)) {
                $personal = PersonalProfile::where('user_id', $user->id)->first();
                $personal->photo = null;
                $personal->save();
                $setting = UserSetting::firstOrCreate(
                    ['user_id' => $user->id],
                    ['photo' => null] // Add the default photo value here
                );
                return ResponseHelper::withSuccess(ucwords(str_replace('_', ' ', $field)) . ' removed successfully.', $setting);
            }

            $request->validate([
                'photo' => 'required|file|image|max:5120',
            ]);

            $folder = env('APP_NAME', 'HappyHomes') . '/user_settings/photos';

            try {
                $uploadedImage = Cloudinary::upload($request->file('photo')->getRealPath(), [
                    'folder' => $folder,
                    'resource_type' => 'image',
                ]);

                $photoUrl = $uploadedImage->getSecurePath();

                $personal = PersonalProfile::where('user_id', $user->id)->first();
                $personal->photo = $photoUrl;
                $personal->save();
                $setting = UserSetting::firstOrCreate(
                    ['user_id' => $user->id],
                    ['photo' => $photoUrl] // Add the default photo value here
                );

                return ResponseHelper::withSuccess(ucwords(str_replace('_', ' ', $field)) . ' updated successfully.', $setting);
            } catch (\Exception $e) {
                return ResponseHelper::withError('Failed to upload photo: ' . $e->getMessage());
            }
        } else {

            // If the request wants to remove the photo (null it)
            if ($request->has('remove') && filter_var($request->input('remove'), FILTER_VALIDATE_BOOLEAN)) {
                $setting = UserSetting::firstOrCreate(['user_id' => $user->id]);
                $setting->{$field} = null;
                $setting->save();

                return ResponseHelper::withSuccess(ucwords(str_replace('_', ' ', $field)) . ' removed successfully.', $setting);
            }

            $request->validate([
                'photo' => 'required|file|image|max:5120',
            ]);

            $folder = env('APP_NAME', 'HappyHomes') . '/user_settings/photos';

            try {
                $uploadedImage = Cloudinary::upload($request->file('photo')->getRealPath(), [
                    'folder' => $folder,
                    'resource_type' => 'image',
                ]);

                $photoUrl = $uploadedImage->getSecurePath();

                $setting = UserSetting::firstOrCreate(['user_id' => $user->id]);
                $setting->{$field} = $photoUrl;
                $setting->save();

                return ResponseHelper::withSuccess(ucwords(str_replace('_', ' ', $field)) . ' updated successfully.', $setting);
            } catch (\Exception $e) {
                return ResponseHelper::withError('Failed to upload photo: ' . $e->getMessage());
            }
        }
    }
}
