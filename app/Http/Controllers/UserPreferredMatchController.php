<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPreferredMatch;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

class UserPreferredMatchController extends Controller
{
    public function index()
    {
        $matches = UserPreferredMatch::with('religions')->get();
        return ResponseHelper::withSuccess('Preferred matches retrieved successfully.', $matches);
    }

    public function store(Request $request, User $user)
    {
        $request->validate([
            'preferred_matches' => 'required|array|min:1',
            'preferred_matches.*.relationship_type' => 'required|string',
            'preferred_matches.*.gender' => 'required|array|min:1',
            'preferred_matches.*.age_range' => 'required|array|min:1',
            'preferred_matches.*.height_range' => 'required|array|min:1',
            'preferred_matches.*.marital_status' => 'required|array|min:1',
            'preferred_matches.*.ethnicity' => 'required|array|min:1',
            'preferred_matches.*.genotype' => 'required|array|min:1',
            'preferred_matches.*.nationality' => 'required|array|min:1',
            'preferred_matches.*.state' => 'required|array|min:1',
            'preferred_matches.*.religions' => 'required|array|min:1',
            'preferred_matches.*.religions.*.religion' => 'required|string',
            'preferred_matches.*.religions.*.denomination' => 'nullable|string',
        ]);

        $createdMatches = [];

        foreach ($request->preferred_matches as $matchData) {
            $exists = UserPreferredMatch::where('user_id', $user->id)
                ->where('relationship_type', $matchData['relationship_type'])
                ->exists();

            if ($exists) {
                return ResponseHelper::withError(
                    "Relationship type '{$matchData['relationship_type']}' already exists.",
                    422
                );
            }

            $match = UserPreferredMatch::create([
                'user_id' => $user->id,
                'relationship_type' => $matchData['relationship_type'],
                'gender' => $matchData['gender'],
                'age_range' => $matchData['age_range'],
                'height_range' => $matchData['height_range'],
                'marital_status' => $matchData['marital_status'],
                'ethnicity' => $matchData['ethnicity'],
                'genotype' => $matchData['genotype'],
                'nationality' => $matchData['nationality'],
                'state' => $matchData['state'],
            ]);

            foreach ($matchData['religions'] as $religion) {
                $match->religions()->create([
                    'religion' => $religion['religion'],
                    'denomination' => $religion['denomination'] ?? null,
                ]);
            }

            $createdMatches[] = $match->load('religions');
        }

        return ResponseHelper::withSuccess('Preferred matches created successfully.', $createdMatches);
    }



    public function show(User $user, $id)
    {
        $match = UserPreferredMatch::with('religions')
            ->where('user_id', $user->id)
            ->find($id);

        if (!$match) {
            return ResponseHelper::withError('Preferred match not found for this user.');
        }

        return ResponseHelper::withSuccess('Preferred match retrieved successfully.', $match);
    }

    public function update(Request $request, User $user, $id)
    {
        $request->validate([
            'relationship_type' => 'required|string',
            'gender' => 'nullable|array',
            'age_range' => 'nullable|array',
            'marital_status' => 'nullable|array',
            'ethnicity' => 'nullable|array',
            'genotype' => 'nullable|array',
            'nationality' => 'nullable|array',
            'state' => 'nullable|array',
            'religions' => 'nullable|array',
            'religions.*.religion' => 'required_with:religions|string',
            'religions.*.denomination' => 'nullable|string',
        ]);

        $match = UserPreferredMatch::where('user_id', $user->id)->findOrFail($id);

        $match->update([
            'relationship_type' => $request->relationship_type,
            'gender' => $request->gender,
            'age_range' => $request->age_range,
            'marital_status' => $request->marital_status,
            'ethnicity' => $request->ethnicity,
            'genotype' => $request->genotype,
            'nationality' => $request->nationality,
            'state' => $request->state,
        ]);

        // Refresh religions
        $match->religions()->delete();

        if ($request->religions) {
            foreach ($request->religions as $religion) {
                $match->religions()->create([
                    'religion' => $religion['religion'],
                    'denomination' => $religion['denomination'] ?? null,
                ]);
            }
        }

        return ResponseHelper::withSuccess('Preferred match updated successfully.', $match->load('religions'));
    }

    public function listForUser(User $user)
    {
        $matches = UserPreferredMatch::with('religions')
            ->where('user_id', $user->id)
            ->get();

        if ($matches->isEmpty()) {
            return ResponseHelper::withError('No preferred matches found for this user.');
        }
        // return "here";
        return ResponseHelper::withSuccess('Preferred matches retrieved successfully.', $matches);
    }
}
