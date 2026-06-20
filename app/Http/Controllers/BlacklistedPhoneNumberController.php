<?php

namespace App\Http\Controllers;

use App\Models\BlacklistedPhoneNumber;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class BlacklistedPhoneNumberController extends Controller
{
    public function index()
    {
        $blacklisted = BlacklistedPhoneNumber::where('is_blacklisted', true)->get();

        $formatted = $blacklisted->map(function ($record) {
            return [
                'id' => $record->id,
                'phone_number' => $record->phone_number,
                'reason' => $record->reason,
                'blacklisted_at' => $record->blacklisted_at ? $record->blacklisted_at->toIso8601String() : null,
                'days_blacklisted' => $record->blacklisted_at ? now()->diffInDays($record->blacklisted_at) : 0,
            ];
        });

        return ResponseHelper::withSuccess('Blacklisted phone numbers retrieved successfully.', $formatted);
    }

    public function destroy($phone_number)
    {
        $record = BlacklistedPhoneNumber::where('phone_number', $phone_number)->first();

        if (!$record) {
            return ResponseHelper::withError('Phone number not found in blacklist records.', 404);
        }

        $record->delete();

        return ResponseHelper::withSuccess('Phone number whitelisted successfully.');
    }
}
