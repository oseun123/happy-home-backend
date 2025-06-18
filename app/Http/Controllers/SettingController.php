<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

class SettingController extends Controller
{
    // Get all settings
    public function index()
    {
        return ResponseHelper::withSuccess('Settings retrieved successfully.', Setting::all());
    }

    // Get a specific setting by key
    public function show($key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return ResponseHelper::withError('Setting not found.');
        }

        return ResponseHelper::withSuccess('Setting retrieved successfully.', $setting);
    }

    // Create a new setting
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|unique:settings,key',
            'value' => 'required|array', // Must be an array
        ]);

        $setting = Setting::create([
            'key' => $request->key,
            'value' => $request->value,
        ]);

        return ResponseHelper::withSuccess('Setting created successfully.', $setting);
    }

    // Update an existing setting
    // public function update(Request $request, $key)
    // {
    //     $setting = Setting::where('key', $key)->first();

    //     if (!$setting) {
    //         return ResponseHelper::withError('Setting not found.');
    //     }

    //     $request->validate(['value' => 'required|array']);

    //     $setting->update(['value' => $request->value]);

    //     return ResponseHelper::withSuccess('Setting updated successfully.', $setting);
    // }

    public function update(Request $request, $key)
{
    $setting = Setting::where('key', $key)->first();

    if (!$setting) {
        return ResponseHelper::withError('Setting not found.');
    }

    $request->validate([
        'value' => 'required|array'
    ]);

    $incomingValues = $request->value;
    $currentValues = is_array($setting->value) ? $setting->value : json_decode($setting->value, true);
    $currentValues = $currentValues ?: [];

    // If the key is "relationship_type", perform special validation
    if ($key === 'relationship_type') {

        // Get items being *removed* from the current setting
        $removedValues = array_diff($currentValues, $incomingValues);

        if (!empty($removedValues)) {
            // Check if any user_preferred_matches is still using the removed values
            $conflictExists = \DB::table('user_preferred_matches')
                ->where(function ($query) use ($removedValues) {
                    foreach ($removedValues as $value) {
                        $query->orWhereJsonContains('relationship_type', $value);
                    }
                })
                ->exists();

            if ($conflictExists) {
                return ResponseHelper::withError('Cannot update. One or more relationship types are in use by user preferences.');
            }
        }
    }

    $setting->update([
        'value' => $incomingValues
    ]);

    return ResponseHelper::withSuccess('Setting updated successfully.', $setting);
}


    // Delete a setting
    public function destroy($key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return ResponseHelper::withError('Setting not found.');
        }

        $setting->delete();

        return ResponseHelper::withSuccess('Setting deleted successfully.');
    }
}
