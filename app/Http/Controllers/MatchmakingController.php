<?php

namespace App\Http\Controllers;

use Log;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;


class MatchmakingController extends Controller
{

    public function getUserMatches(Request $request, User $user)
    {
        $search = $request->query('search');

        $potentialMatches = User::with([
            'userBioData.religions',
            'contact',
            'personalProfile',
            'addressVerifications'
        ])
            ->where('id', '!=', $user->id);

        // for search query

        if ($search) {
            $potentialMatches->whereHas('personalProfile', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('middle_name', 'like', '%' . $search . '%');
                });
            });
        }

        return $this->innerLogic($request, $user, $potentialMatches);
    }






    private function innerLogic($request, $user, $potentialMatches)
    {


        $request->validate([
            'relationship_type' => 'required|string|max:255',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        // Set default pagination values
        $perPage = $request->input('per_page', 10); // Default to 10 items per page
        $page = $request->input('page', 1); // Default to first page

        // Step 1: Get preferred matches for the relationship type
        $preferredMatches = $user->preferredMatches()
            ->where('relationship_type', $request->input('relationship_type'))
            ->with('religions')
            ->get();

        if ($preferredMatches->isEmpty()) {
            return ResponseHelper::withError('No preferred matches found for the specified relationship type.');
        }



        // Step 3: Apply optional pre-filters
        $preFilters = [
            'nationality' => 'contact.nationality',
            'state' => 'contact.state',
            'gender' => 'personalProfile.gender',
            'marital_status' => 'userBioData.marital_status',
            'ethnicity' => 'userBioData.ethnicity',
            'height_range' => 'userBioData.height_range',
            'genotype' => 'userBioData.genotype',
        ];

        foreach ($preFilters as $filter => $relationField) {
            if ($request->filled($filter)) {
                $filterValue = $request->input($filter);
                $potentialMatches->whereHas(explode('.', $relationField)[0], function ($query) use ($relationField, $filterValue) {
                    $field = explode('.', $relationField)[1];
                    $query->where($field, $filterValue);
                });
            }
        }

        // Handle age range filter
        if ($request->filled('age_range')) {
            $ageRange = $request->input('age_range');
            $potentialMatches->whereHas('personalProfile', function ($query) use ($ageRange) {
                $query->whereNotNull('date_of_birth');
                if (str_contains($ageRange, '-')) {
                    [$minAge, $maxAge] = explode('-', $ageRange);
                    $minDate = now()->subYears($maxAge)->format('Y-m-d');
                    $maxDate = now()->subYears($minAge)->format('Y-m-d');
                    $query->whereBetween('date_of_birth', [$minDate, $maxDate]);
                } elseif (str_ends_with($ageRange, '+')) {
                    $minAge = (int) str_replace('+', '', $ageRange);
                    $maxDate = now()->subYears($minAge)->format('Y-m-d');
                    $query->where('date_of_birth', '<=', $maxDate);
                }
            });
        }

        // Get all potential matches (before scoring)
        $potentialMatches = $potentialMatches->get();

        $threshold = optional($user->settings)->matchup_settings ?? config('matchmaking.default_threshold', 30);

        // Calculate scores and filter
        $allMatches = $potentialMatches->map(function ($otherUser) use ($preferredMatches, $threshold, $user) {
            $score = $this->calculateMatchScore($preferredMatches, $otherUser);

            if ($score >= $threshold) {
                return [
                    'user' => $this->formatUserData($otherUser, $user),
                    'match_score' => $score,
                    'matched_criteria' => $this->getMatchedCriteria($preferredMatches, $otherUser)
                ];
            }
            return null;
        })
            ->filter()
            ->sortByDesc('match_score')
            ->values();

        // Implement pagination manually since we're working with a collection
        $totalMatches = $allMatches->count();
        $paginatedMatches = $allMatches->forPage($page, $perPage);

        return ResponseHelper::withSuccess('User matches retrieved successfully.', [
            'matches' => $paginatedMatches->values(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalMatches,
                'total_pages' => ceil($totalMatches / $perPage),
                'has_more' => ($page * $perPage) < $totalMatches,
            ],
            'threshold' => $threshold,
            // 'requested_nationalities' => $preferredMatches->pluck('nationality')->unique()->flatten()
        ]);
    }






    protected function formatUserData(User $otherUser, User $currentUser)
    {
        $isSubscribedByMe = $currentUser->hasSubscribedTo($otherUser);
        $hasSubscribedToMe = $currentUser->isSubscribedBy($otherUser);
        $isMutuallySubscribed = $currentUser->isMutuallySubscribedWith($otherUser);
        $isFavoritedByMe = $currentUser->hasFavorited($otherUser);
        $is_blocked = $currentUser->hasBlocked($otherUser);
        $has_address_verified = $otherUser->hasVerifiedAddress();
        return [
            'id' => $otherUser->id,
            'name' => $otherUser->personalProfile->first_name,
            'photo' => optional($otherUser->personalProfile)->photo,
            'cover_photo' => optional($otherUser->settings)->cover_photo,
            'short_bio' => optional($otherUser->settings)->short_bio,
            'state' =>  !$otherUser->settings->hide_location ? optional($otherUser->contact)->state : null,
            'age' => !$otherUser->settings->hide_age ? $this->calculateAge(optional($otherUser->personalProfile)->date_of_birth) : null,
            'is_favorited_by_me' => $isFavoritedByMe,
            'is_subscribed_by_me' => $isSubscribedByMe,
            'has_subscribed_to_me' => $hasSubscribedToMe,
            'is_mutaul_to_me' => $isMutuallySubscribed,
            'is_address_verified' => false, // temporary
            'is_blocked_by_me' => $is_blocked,
            'has_address_verified' => $has_address_verified
        ];
    }




    protected function calculateAge($dateOfBirth)
    {
        return $dateOfBirth ? Carbon::parse($dateOfBirth)->age : null;
    }

    protected function calculateMatchScore($preferredMatches, $otherUser)
    {
        $score = 0;
        $maxPossibleScore = 0;
        $weights = config('matchmaking.field_weights', [
            'nationality' => 1.5,
            'gender' => 1.2,
            'religion' => 1.4,
            'denomination' => 1.3,
            'state' => 1.1,
            'age_range' => 1.1,
            'height_range' => 1.1,
            'marital_status' => 1.0,
            'ethnicity' => 1.0,
            'genotype' => 1.0,
        ]);

        $userAge = $this->calculateAge(optional($otherUser->personalProfile)->date_of_birth);

        foreach ($preferredMatches as $preferred) {
            $fieldsToCompare = [
                'nationality' => optional($otherUser->contact)->nationality,
                'state' => optional($otherUser->contact)->state,
                'gender' => optional($otherUser->personalProfile)->gender,
                'marital_status' => optional($otherUser->userBioData)->marital_status,
                'ethnicity' => optional($otherUser->userBioData)->ethnicity,
                'height_range' => optional($otherUser->userBioData)->height_range,
                'genotype' => optional($otherUser->userBioData)->genotype,
            ];

            // Log::info('Height comparison', [
            //     'preferred' => $preferred->height_range,
            //     'user' => optional($otherUser->userBioData)->height_range
            // ]);

            foreach ($fieldsToCompare as $field => $otherValues) {
                $preferredValues = (array) ($preferred->$field ?? []);
                $otherValues = (array) $otherValues;

                if (!empty($preferredValues)) {
                    $weight = $weights[$field] ?? 1.0;
                    $maxPossibleScore += $weight;

                    if (!empty($otherValues)) {
                        $intersection = array_intersect($preferredValues, $otherValues);
                        if (!empty($intersection)) {
                            $score += $weight;
                        }
                    }
                }
            }

            // Age range
            if (!empty($preferred->age_range)) {
                $weight = $weights['age_range'] ?? 1.0;
                $maxPossibleScore += $weight;

                if ($userAge && $this->checkAgeMatch($preferred->age_range, $userAge)) {
                    $score += $weight;
                }
            }

            // Religion & Denomination
            $preferredReligions = $preferred->religions ?? collect();
            $userReligions = optional($otherUser->userBioData)->religions ?? collect();

            $preferredReligionsList = $preferredReligions->pluck('religion')->filter()->unique();
            if ($preferredReligionsList->isNotEmpty()) {
                $weight = $weights['religion'] ?? 1.0;
                $maxPossibleScore += $weight;

                $userReligionsList = $userReligions->pluck('religion')->filter()->unique();
                if ($userReligionsList->intersect($preferredReligionsList)->isNotEmpty()) {
                    $score += $weight;
                }
            }

            $preferredDenominations = $preferredReligions->pluck('denomination')->filter()->unique();
            if ($preferredDenominations->isNotEmpty()) {
                $weight = $weights['denomination'] ?? 1.0;
                $maxPossibleScore += $weight;

                $userDenominations = $userReligions->pluck('denomination')->filter()->unique();
                if ($userDenominations->intersect($preferredDenominations)->isNotEmpty()) {
                    $score += $weight;
                }
            }
        }

        return $maxPossibleScore > 0 ? min(round(($score / $maxPossibleScore) * 100), 100) : 0;
    }

    protected function checkAgeMatch($preferredAgeRanges, $userAge)
    {
        $preferredAgeRanges = (array) $preferredAgeRanges;

        foreach ($preferredAgeRanges as $range) {
            if (str_contains($range, '-')) {
                list($minAge, $maxAge) = explode('-', $range);
                if ($userAge >= $minAge && $userAge <= $maxAge) {
                    return true;
                }
            } elseif (str_ends_with($range, '+')) {
                $minAge = str_replace('+', '', $range);
                if ($userAge >= $minAge) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getMatchedCriteria($preferredMatches, $otherUser)
    {
        $matchedCriteria = [];
        $userAge = $this->calculateAge(optional($otherUser->personalProfile)->date_of_birth);

        foreach ($preferredMatches as $preferred) {
            $fieldsToCompare = [
                'nationality' => optional($otherUser->contact)->nationality,
                'state' => optional($otherUser->contact)->state,
                'gender' => optional($otherUser->personalProfile)->gender,
                'marital_status' => optional($otherUser->userBioData)->marital_status,
                'ethnicity' => optional($otherUser->userBioData)->ethnicity,
                'genotype' => optional($otherUser->userBioData)->genotype,
                'height_range' => optional($otherUser->userBioData)->height_range,
            ];

            foreach ($fieldsToCompare as $field => $otherValues) {
                $preferredValues = (array) ($preferred->$field ?? []);
                $otherValues = (array) $otherValues;

                $intersection = array_intersect($preferredValues, $otherValues);
                if (!empty($intersection)) {
                    $matchedCriteria[$field] = array_values(array_unique($intersection));
                }
            }

            if ($userAge && !empty($preferred->age_range)) {
                if ($this->checkAgeMatch($preferred->age_range, $userAge)) {
                    $matchedCriteria['age_range'] = [$userAge];
                }
            }

            $preferredReligions = $preferred->religions ?? collect();
            $userReligions = optional($otherUser->userBioData)->religions ?? collect();

            $religionMatch = $preferredReligions->pluck('religion')
                ->intersect($userReligions->pluck('religion'))
                ->unique()
                ->values()
                ->toArray();

            if (!empty($religionMatch)) {
                $matchedCriteria['religion'] = $religionMatch;
            }

            $denominationMatch = $preferredReligions->pluck('denomination')
                ->intersect($userReligions->pluck('denomination'))
                ->unique()
                ->values()
                ->toArray();

            if (!empty($denominationMatch)) {
                $matchedCriteria['denomination'] = $denominationMatch;
            }
        }

        return $matchedCriteria;
    }



    public function getFavoritesWithMatchScore(Request $request, User $user)
    {

        // Step 2: Get potential matches with relationships

        $potentialMatches = $user->favorites()->with(['userBioData.religions', 'contact', 'personalProfile']);

        return   $this->innerLogic($request, $user, $potentialMatches);
    }
    public function getSubscriptionWithMatchScore(Request $request, User $user)
    {

        // Step 2: Get potential matches with relationships

        $iSubscribedTo = $user->subscriptions()->pluck('users.id')->toArray();
        $subscribedToMe = $user->subscribers()->pluck('users.id')->toArray();
        // Mutuals
        $mutuals = array_intersect($iSubscribedTo, $subscribedToMe);

        // I subscribed to them, but they didn't subscribe back
        $onlyISubscribed = array_diff($iSubscribedTo, $mutuals);


        $onlyISubscribedUsers = User::whereIn('id', $onlyISubscribed)->with(['userBioData', 'contact', 'personalProfile']);

        // $potentialMatches = $user->favorites()->with(['userBioData.religions', 'contact', 'personalProfile']);

        return   $this->innerLogic($request, $user, $onlyISubscribedUsers);
    }
    public function getInterestedWithMatchScore(Request $request, User $user)
    {

        // Step 2: Get potential matches with relationships

        $iSubscribedTo = $user->subscriptions()->pluck('users.id')->toArray();
        $subscribedToMe = $user->subscribers()->pluck('users.id')->toArray();
        // Mutuals
        $mutuals = array_intersect($iSubscribedTo, $subscribedToMe);


        // They subscribed to me, but I didn't subscribe back
        $onlyTheySubscribed = array_diff($subscribedToMe, $mutuals);

        $onlyTheySubscribedUsers = User::whereIn('id', $onlyTheySubscribed)->with(['userBioData', 'contact', 'personalProfile']);



        return   $this->innerLogic($request, $user, $onlyTheySubscribedUsers);
    }

    public function getMutaulsWithMatchScore(Request $request, User $user)
    {

        // Step 2: Get potential matches with relationships

        $iSubscribedTo = $user->subscriptions()->pluck('users.id')->toArray();
        $subscribedToMe = $user->subscribers()->pluck('users.id')->toArray();
        // Mutuals
        $mutuals = array_intersect($iSubscribedTo, $subscribedToMe);

        $mutualUsers = User::whereIn('id', $mutuals)->with(['userBioData', 'contact', 'personalProfile']);



        return   $this->innerLogic($request, $user, $mutualUsers);
    }
}
