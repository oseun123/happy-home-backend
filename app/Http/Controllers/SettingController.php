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
    public function update(Request $request, $key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return ResponseHelper::withError('Setting not found.');
        }

        $request->validate(['value' => 'required|array']);

        $setting->update(['value' => $request->value]);

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
