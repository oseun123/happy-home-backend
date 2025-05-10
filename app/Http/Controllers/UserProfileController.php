<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Helpers\ResponseHelper;

class UserProfileController extends Controller
{
    //

    public function userProfile(User $user, $targetUserId)
    {


        $otherUser = User::findOrFail($targetUserId);

        if (!$otherUser) {

            return ResponseHelper::withError('Invalid user');
        }

        $is_blocked = $otherUser->hasBlocked($user);

        // dd($is_blocked);

        if ($is_blocked) {

            return ResponseHelper::withError('You have been blocked to view this profile.');
        }

        $is_mutual = $user->isMutuallySubscribedWith($otherUser);
        $has_address_verified = $otherUser->hasVerifiedAddress();

        $profile = [
            'user_id' => $targetUserId,
            'photo' => optional($otherUser->personalProfile)->photo,
            'photo_1' => optional($otherUser->settings)->photo_1,
            'photo_2' => optional($otherUser->settings)->photo_2,
            'photo_3' => optional($otherUser->settings)->photo_3,
            'photo_4' => optional($otherUser->settings)->photo_4,
            'cover_photo' => optional($otherUser->settings)->cover_photo,
            'state' =>  !$otherUser->settings->hide_location ? optional($otherUser->contact)->state : null,
            'age' => !$otherUser->settings->hide_age ? $this->calculateAge(optional($otherUser->personalProfile)->date_of_birth) : null,
            'short_bio' => optional($otherUser->settings)->short_bio,
            'interest' => optional($otherUser->hobbiesInterest)->interest,
            'hobbies' => optional($otherUser->hobbiesInterest)->hobbies,
            'language_spoken' => optional($otherUser->hobbiesInterest)->language_spoken,
            'about_me' => [
                "gender" => optional($otherUser->personalProfile)->gender,
                "occupation" => optional($otherUser->userBioData)->occupation,
                "marital_status" => optional($otherUser->userBioData)->marital_status,
                "genotype" => optional($otherUser->userBioData)->genotype,
                "height_range" => optional($otherUser->userBioData)->height_range,
                "weight_range" => optional($otherUser->userBioData)->weight_range,
                "religion" => optional($otherUser->userBioData)->religions,
                "nationality" => optional($otherUser->contact)->nationality,
                "age" => !$otherUser->settings->hide_age ? $this->calculateAge(optional($otherUser->personalProfile)->date_of_birth) : null,


            ],

            "looking_for" => $otherUser->preferredMatches()
                ->with('religions')
                ->get(),

            "available_retry" => $otherUser->countAvailableRetries(),


            "contact_details" => $is_mutual ? ['address' => $otherUser->contact, 'phone' => optional($otherUser->personalProfile)->phone_number, 'email' => $otherUser->email]  : null,

            'has_address_verified' => $has_address_verified

        ];

        return ResponseHelper::withSuccess('User profile fetched successfully', $profile);
    }

    // just for login
    public function userProfileLogin(User $user, $targetUserId)
    {


        $otherUser = User::findOrFail($targetUserId);

        if (!$otherUser) {

            return ResponseHelper::withError('Invalid user');
        }

        $is_mutual = $user->isMutuallySubscribedWith($otherUser);
        $has_address_verified = $otherUser->hasVerifiedAddress();

        $profile = [
            'user_id' => $targetUserId,
            'photo' => optional($otherUser->personalProfile)->photo,
            'photo_1' => optional($otherUser->settings)->photo_1,
            'photo_2' => optional($otherUser->settings)->photo_2,
            'photo_3' => optional($otherUser->settings)->photo_3,
            'photo_4' => optional($otherUser->settings)->photo_4,
            'cover_photo' => optional($otherUser->settings)->cover_photo,
            'state' =>  !optional($otherUser->settings)->hide_location ? optional($otherUser->contact)->state : null,
            'age' => !optional($otherUser->settings)->hide_age ? $this->calculateAge(optional($otherUser->personalProfile)->date_of_birth) : null,
            'short_bio' => optional($otherUser->settings)->short_bio,
            'interest' => optional($otherUser->hobbiesInterest)->interest,
            'hobbies' => optional($otherUser->hobbiesInterest)->hobbies,
            'language_spoken' => optional($otherUser->hobbiesInterest)->language_spoken,
            'about_me' => [
                "gender" => optional($otherUser->personalProfile)->gender,
                "occupation" => optional($otherUser->userBioData)->occupation,
                "marital_status" => optional($otherUser->userBioData)->marital_status,
                "genotype" => optional($otherUser->userBioData)->genotype,
                "height_range" => optional($otherUser->userBioData)->height_range,
                "weight_range" => optional($otherUser->userBioData)->weight_range,
                "religion" => optional($otherUser->userBioData)->religions,
                "nationality" => optional($otherUser->contact)->nationality,
                "age" => !optional($otherUser->settings)->hide_age ? $this->calculateAge(optional($otherUser->personalProfile)->date_of_birth) : null,


            ],

            "looking_for" => $otherUser->preferredMatches()
                ->with('religions')
                ->get(),

            "available_retry" => $otherUser->countAvailableRetries(),


            "contact_details" => $is_mutual ? ['address' => $otherUser->contact, 'phone' => optional($otherUser->personalProfile)->phone_number, 'email' => $otherUser->email]  : null,

            "profile_setup" => isset($user->personalProfile) && count($user->preferredMatches) ? true : false,
            'has_address_verified' => $has_address_verified,
            "is_account_deleted" => $user->daysUntilDeletion()

        ];

        return $profile;
    }

    protected function calculateAge($dateOfBirth)
    {
        return $dateOfBirth ? Carbon::parse($dateOfBirth)->age : null;
    }
}
