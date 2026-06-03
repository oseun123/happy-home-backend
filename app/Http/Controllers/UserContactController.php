<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserContact;
use App\Helpers\ResponseHelper;

class UserContactController extends Controller
{

    public function index()
    {
        $contacts = UserContact::with('user')->get(); // eager load user if needed

        return ResponseHelper::withSuccess('All contacts retrieved successfully.', $contacts);
    }
    // Store user contact info
    public function store(Request $request, User $user)
    {


        $request->validate([
            'house_number' => 'required|string',
            'street_name' => 'required|string',
            'land_mark' => 'nullable|string',
            'lga' => 'required|string',
            'postal_code' => 'required|string',
            'state' => 'required|string',
            'nationality' => 'required|string',
        ]);

        if ($user->contact) {
            return ResponseHelper::withError('User contact already exists.');
        }

        $contact = $user->contact()->create($request->all());

        return ResponseHelper::withSuccess('User contact created successfully.', $contact);
    }

    // Show user contact info
    public function show(Request $request, User $user)
    {
        $contact = $user->contact;

        if (!$contact) {
            return ResponseHelper::withError('User contact not found.');
        }

        if ($request->query('with_house_address') === 'true') {
            $contact->makeVisible('house_number');
        }

        return ResponseHelper::withSuccess('User contact retrieved successfully.', $contact);
    }

    // Update user contact info
    public function update(Request $request, User $user)
    {
        $contact = $user->contact;

        if (!$contact) {
            return ResponseHelper::withError('User contact not found.');
        }

        $request->validate([
            'house_number' => 'sometimes|required|string',
            'street_name' => 'sometimes|required|string',
            'land_mark' => 'nullable|string',
            'lga' => 'sometimes|required|string',
            'postal_code' => 'sometimes|required|string',
            'state' => 'sometimes|required|string',
            'nationality' => 'sometimes|required|string',
        ]);

        $contact->update($request->all());

        return ResponseHelper::withSuccess('User contact updated successfully.', $contact);
    }
}
