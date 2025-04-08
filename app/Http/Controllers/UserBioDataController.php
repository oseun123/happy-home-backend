<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserBioData;
use App\Models\UserReligion;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

class UserBioDataController extends Controller
{

    public function index()
    {
        $bioData = UserBioData::with('user', 'religions')->get();

        if ($bioData->isEmpty()) {
            return ResponseHelper::withError('No bio-data found.');
        }

        return ResponseHelper::withSuccess('User bio-data retrieved successfully.', $bioData);
    }


    public function store(Request $request, User $user)
    {
        $request->validate([
            'height_range' => 'required|string|max:255',
            'weight_range' => 'required|string|max:255',
            'ethnicity' => 'required|string|max:255',
            'genotype' => 'required|string|max:10',
            'marital_status' => 'required|string|max:50',
            'occupation' => 'required|string|max:255',
            'religion' => 'required|array',
            'religion.*.religion' => 'required|string|max:255',
            'religion.*.denomination' => 'nullable|string|max:255',
        ]);

        // Ensure user does not already have a bio data
        if ($user->bioData) {
            return ResponseHelper::withError('User already has a bio data.');
        }

        $bioData = UserBioData::create([
            'user_id' => $user->id,
            'height_range' => $request->height_range,
            'weight_range' => $request->weight_range,
            'ethnicity' => $request->ethnicity,
            'genotype' => $request->genotype,
            'marital_status' => $request->marital_status,
            'occupation' => $request->occupation,
        ]);

        // Store religions
        foreach ($request->religion as $religion) {
            UserReligion::create([
                'user_bio_data_id' => $bioData->id,
                'religion' => $religion['religion'],
                'denomination' => $religion['denomination'] ?? null,
            ]);
        }

        return ResponseHelper::withSuccess('User bio data created successfully.', $bioData->load('religions'));
    }

    public function show(User $user)


    {
        $bioData = $user->userBioData()->with('religions')->first();

        if (!$bioData) {
            return ResponseHelper::withError('User bio-data not found.');
        }

        return ResponseHelper::withSuccess('User bio-data retrieved successfully.', $bioData);
    }

    public function update(Request $request, User $user)
    {
        $bioData = $user->userBioData;

        if (!$bioData) {
            return ResponseHelper::withError('User bio-data not found.');
        }

        $request->validate([
            'height_range' => 'sometimes|string|max:255',
            'weight_range' => 'sometimes|string|max:255',
            'ethnicity' => 'sometimes|string|max:255',
            'genotype' => 'sometimes|string|max:10',
            'marital_status' => 'sometimes|string|max:50',
            'occupation' => 'sometimes|string|max:255',
            'religion' => 'sometimes|array',
            'religion.*.religion' => 'required_with:religion|string|max:255',
            'religion.*.denomination' => 'nullable|string|max:255',
        ]);

        $bioData->update($request->only([
            'height_range',
            'weight_range',
            'ethnicity',
            'genotype',
            'marital_status',
            'occupation',
        ]));

        // Update religions if provided
        if ($request->has('religion')) {
            $bioData->religions()->delete(); // Remove old religions

            foreach ($request->religion as $religion) {
                UserReligion::create([
                    'user_bio_data_id' => $bioData->id,
                    'religion' => $religion['religion'],
                    'denomination' => $religion['denomination'] ?? null,
                ]);
            }
        }

        return ResponseHelper::withSuccess('User bio-data updated successfully.', $bioData->load('religions'));
    }
}
