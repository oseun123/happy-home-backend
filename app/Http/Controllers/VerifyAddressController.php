<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;






class VerifyAddressController extends Controller
{

    public function verify(Request $request, User $user)
    {
        $request->validate([
            'longitude' => 'required|string',
            'latitude' => 'required|string',
        ]);

        // dd($user);

        try {




            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'AppId' => env('DOJAH_APP_ID'),
                'Authorization' => env('DOJAH_SECRET_KEY'),
            ])->get(env('DOJAH_VERIFY_ADDRESS_URL'), [
                'longitude' => $request->longitude,
                'latitude' => $request->latitude, // Change as needed
            ]);

            // Check for API errors
            if ($response->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Geocoding service unavailable',
                    'details' => $response->json()
                ], 502);
            }

            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error while processing geocoding'
            ], 500);
        }
    }
}
