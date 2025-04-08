<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserHobbiesInterest;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

class UserHobbiesInterestController extends Controller
{
    // Get all hobbies & interests
    public function index()
    {
        return ResponseHelper::withSuccess('All hobbies and interests retrieved successfully.', UserHobbiesInterest::all());
    }

    // Create hobbies & interests for a user
    public function store(Request $request, User $user)
    {
        $request->validate([
            'hobbies' => 'required|array',
            'interest' => 'required|array',
            'language_spoken' => 'required|array',
        ]);

        $record = UserHobbiesInterest::create([
            'user_id' => $user->id,
            'hobbies' => $request->hobbies,
            'interest' => $request->interest,
            'language_spoken' => $request->language_spoken,
        ]);

        return ResponseHelper::withSuccess('Hobbies and interests created successfully.', $record);
    }

    // Get a user's hobbies & interests
    public function show(User $user)
    {
        $record = $user->hobbiesInterest;

        if (!$record) {
            return ResponseHelper::withError('Hobbies and interests not found for this user.');
        }

        return ResponseHelper::withSuccess('Hobbies and interests retrieved successfully.', $record);
    }

    // Update a user's hobbies & interests
    public function update(Request $request, User $user)
    {
        $request->validate([
            'hobbies' => 'required|array',
            'interest' => 'required|array',
            'language_spoken' => 'required|array',
        ]);

        $record = $user->hobbiesInterest;

        if (!$record) {
            return ResponseHelper::withError('Hobbies and interests not found for this user.');
        }

        $record->update([
            'hobbies' => $request->hobbies,
            'interest' => $request->interest,
            'language_spoken' => $request->language_spoken,
        ]);

        return ResponseHelper::withSuccess('Hobbies and interests updated successfully.', $record);
    }
}
