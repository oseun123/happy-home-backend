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

        $match = UserPreferredMatch::create([
            'user_id' => $user->id,
            'relationship_type' => $request->relationship_type,
            'gender' => $request->gender,
            'age_range' => $request->age_range,
            'marital_status' => $request->marital_status,
            'ethnicity' => $request->ethnicity,
            'genotype' => $request->genotype,
            'nationality' => $request->nationality,
            'state' => $request->state,
        ]);

        if ($request->religions) {
            foreach ($request->religions as $religion) {
                $match->religions()->create([
                    'religion' => $religion['religion'],
                    'denomination' => $religion['denomination'] ?? null,
                ]);
            }
        }

        return ResponseHelper::withSuccess('Preferred match created successfully.', $match->load('religions'));
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

        return ResponseHelper::withSuccess('Preferred matches retrieved successfully.', $matches);
    }
}
