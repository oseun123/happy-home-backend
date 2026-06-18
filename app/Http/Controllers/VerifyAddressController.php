<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Models\AddressVerification;
use Illuminate\Support\Facades\Http;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Notifications\AddressVerifiedNotification;






class VerifyAddressController extends Controller
{



    public function initializePayment(Request $request, User $user)
    {
        $request->validate([
            'longitude' => 'required|string',
            'latitude' => 'required|string',
        ]);

        $amountSetting = Setting::where('key', 'address_verification_amount')->value('value')[0] ?? 500;
        $amount = (int) $amountSetting;

        $reference = Paystack::genTranxRef();

        $data = [
            "amount" => $amount * 100,
            "email" => $user->email,
            "currency" => "NGN",
            "reference" => $reference,
            "callback_url" => config('app.frontend_url') . '/verify-address-payment',
            "metadata" => [
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'type' => 'address_verification'
            ],
        ];

        try {
            $paymentUrl = Paystack::getAuthorizationUrl($data)->url;

            AddressVerification::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'amount' => $amount,
                'status' => 'pending',
                'longitude' => $request->longitude,
                'latitude' => $request->latitude
            ]);

            return ResponseHelper::withSuccess("Payment initialized successfully", [
                'authorization_url' => $paymentUrl,
                'reference' => $reference
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::withError("Payment initialization failed");
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'reference' => 'required|string'
        ]);

        $reference = $request->reference;

        // Check if this reference has already been successfully verified
        $existingRecord = AddressVerification::where('reference', $reference)
            ->where('status', 'success')
            ->first();

        if ($existingRecord && $existingRecord->dojah_data) {
            return ResponseHelper::withSuccess('Address verified successfully', $existingRecord->dojah_data);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('paystack.secretKey'),
                'Accept' => 'application/json',
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $paymentData = $response->json();

            if ($paymentData['data']['status'] === 'success') {
                $metadata = $paymentData['data']['metadata'];
                $longitude = $metadata['longitude'];
                $latitude = $metadata['latitude'];

                // update  db
                $record = AddressVerification::where('reference', $reference)->first();
                $record->update([
                    'status' => 'success',
                    'paystack_data' => $paymentData
                ]);

                // Call Dojah API
                $dojahResponse = Http::withHeaders([
                    'Accept' => 'application/json',
                    'AppId' => env('DOJAH_APP_ID'),
                    'Authorization' => env('DOJAH_SECRET_KEY'),
                ])->get(env('DOJAH_VERIFY_ADDRESS_URL'), [
                    'longitude' => $longitude,
                    'latitude' => $latitude
                ]);

                $verificationData = $dojahResponse->json();
                \Log::info('Dojah Address Verification API response', [
                    'longitude' => $longitude,
                    'latitude' => $latitude,
                    'response' => $verificationData,
                ]);

                if (!isset($verificationData['entity'])) {
                    $error = $verificationData['error'] ?? 'Address verification failed.';
                    $errorMessage = is_array($error) ? ($error['detail'] ?? json_encode($error)) : $error;
                    return ResponseHelper::withError($errorMessage);
                }



                //Update our DB
                $record = AddressVerification::where('reference', $reference)->first();
                $record->update([
                    'verified_address' => 1,
                    'dojah_data' => $verificationData
                ]);
                $user = $record->user;
                $user->notify(new AddressVerifiedNotification());

                return ResponseHelper::withSuccess('Address verified successfully', $verificationData);
            }

            return ResponseHelper::withError('Payment not successful');
        } catch (\Exception $e) {
            return ResponseHelper::withError("Verification failed", [
                'message' => $e->getMessage()
            ]);
        }
    }
}
