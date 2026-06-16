<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Helpers\ResponseHelper;
use App\Models\Subscription;

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
        $has_blocked_me = $otherUser->hasBlocked($user);
        $is_blocked_by_me = $user->hasBlocked($otherUser);

        $is_favorite_by_me = $user->hasFavorited($otherUser);
        $is_subscribed_by_me = $user->hasSubscribedTo($otherUser);
        $is_subscribed_to_me = $user->isSubscribedBy($otherUser);



        // dd($is_blocked);
        // other user has blocked me
        if ($is_blocked) {

            return ResponseHelper::withError('You have been blocked to view this profile.');
        }

        $is_mutual = $user->isMutuallySubscribedWith($otherUser);
        $has_address_verified = $otherUser->hasVerifiedAddress();

        $verified_address = optional($otherUser->latestAddressVerification)->dojah_data;
        // remove some private details
        if (is_array($verified_address)) {


            unset($verified_address['entity']['formatted_address']);
            unset($verified_address['entity']['address_components']['street_number']);
        }

        $sub_record = $is_subscribed_to_me  && !$is_mutual ? Subscription::where('subscriber_id', $otherUser->id)->where('subscribed_to_id', $user->id)->where('fully_subscribed', 0)->first() : null;
        if ($sub_record) {
            $deadline = Carbon::parse($sub_record->reciprocation_deadline);
            $daysRemaining = Carbon::now()->diffInDays($deadline, false); // false returns negative if past
            // Optional: force it to 0 if it's already past
            $daysRemaining = max(0, $daysRemaining);
            $sub_to_me_record_count =   $daysRemaining;
        } else {
            $sub_to_me_record_count = null;
        }


        $profile = [
            'user_id' => $targetUserId,
            'first_name' => optional($otherUser->personalProfile)->first_name,
            'last_name' => optional($otherUser->personalProfile)->last_name,
            'middle_name' => optional($otherUser->personalProfile)->middle_name,
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

            'has_address_verified' => $has_address_verified,
            'is_favorite_by_me' => $is_favorite_by_me,
            'is_mutual' => $is_mutual,
            'is_subscribed_by_me' => $is_subscribed_by_me,
            'is_subscribed_to_me' => $is_subscribed_to_me,
            "is_account_deleted" => $otherUser->daysUntilDeletion(),
            'has_blocked_me' => $has_blocked_me,
            'is_blocked_by_me' => $is_blocked_by_me,
            'verified_address' => $verified_address,
            'sub_to_me_record_count' =>  $sub_to_me_record_count

        ];

        return ResponseHelper::withSuccess('User profile fetched successfully', $profile);
    }

    // just for login
    public function userProfileLogin(User $user, $targetUserId)
    {


        $otherUser = User::findOrFail($targetUserId);
        $is_favorite_by_me = $user->hasFavorited($otherUser);

        if (!$otherUser) {

            return ResponseHelper::withError('Invalid user');
        }

        $is_mutual = $user->isMutuallySubscribedWith($otherUser);
        $has_address_verified = $otherUser->hasVerifiedAddress();


        $profile = [
            'user_id' => $targetUserId,
            'first_name' => optional($otherUser->personalProfile)->first_name,
            'last_name' => optional($otherUser->personalProfile)->last_name,
            'middle_name' => optional($otherUser->personalProfile)->middle_name,
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

            "profile_setup" => isset($user->personalProfile) && isset($user->userBioData)  && isset($user->hobbiesInterest) && isset($user->contact) ? true : false,
            "personal_setup" => isset($user->personalProfile) ? true : false,
            "biodata_setup" => isset($user->userBioData) ? true : false,
            "contact_setup" => isset($user->contact) ? true : false,
            "hobbies_interest_setup" => isset($user->hobbiesInterest) ? true : false,
            'has_address_verified' => $has_address_verified,
            "is_account_deleted" => $user->daysUntilDeletion(),
            'is_favorite_by_me' => $is_favorite_by_me


        ];

        return $profile;
    }

    protected function calculateAge($dateOfBirth)
    {
        return $dateOfBirth ? Carbon::parse($dateOfBirth)->age : null;
    }
}
