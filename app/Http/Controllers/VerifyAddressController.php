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

        $retryLimitSetting = Setting::where('key', 'address_verification_retry_limit')->value('value')[0] ?? 3;
        $retryLimit = (int) $retryLimitSetting;

        try {
            $paymentUrl = Paystack::getAuthorizationUrl($data)->url;

            AddressVerification::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'amount' => $amount,
                'status' => 'pending',
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'retry_limit' => $retryLimit,
                'retry_count' => 0
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

        // Check if this reference already exists
        $record = AddressVerification::where('reference', $reference)->first();

        \Log::info('verify-payment-address', [
            'reference' => $reference,
            'existingRecord' => $record
        ]);

        if ($record) {
            if ($record->verified_address) {
                return ResponseHelper::withSuccess('Address verified successfully', $record->dojah_data);
            }

            // If the payment is already confirmed on our end (status is success)
            if ($record->status === 'success') {
                if ($record->retry_count < $record->retry_limit) {
                    $widgetId = config('services.dojah.widget_id', '6a33ca593c44efdbfa8c48c4');
                    $dojahUrl = "https://identity.dojah.io?widget_id={$widgetId}&metadata[payment_reference]={$reference}";

                    return ResponseHelper::withSuccess('Payment successful. Redirect to Dojah widget.', [
                        'authorization_url' => $dojahUrl,
                        'reference' => $reference,
                        'verified_address' => false,
                        'retry_count' => $record->retry_count,
                        'retry_limit' => $record->retry_limit,
                        'verification_message' => $record->verification_message,
                    ]);
                } else {
                    return ResponseHelper::withError('Address verification retry limit exceeded. Please make a new payment.');
                }
            }
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('paystack.secretKey'),
                'Accept' => 'application/json',
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $paymentData = $response->json();

            if (isset($paymentData['data']) && $paymentData['data']['status'] === 'success') {
                // update db
                if (!$record) {
                    // Fallback in case record wasn't created on initialization
                    $metadata = $paymentData['data']['metadata'] ?? [];
                    $longitude = $metadata['longitude'] ?? '';
                    $latitude = $metadata['latitude'] ?? '';
                    $amount = isset($paymentData['data']['amount']) ? ($paymentData['data']['amount'] / 100) : 500;
                    $retryLimitSetting = Setting::where('key', 'address_verification_retry_limit')->value('value')[0] ?? 3;
                    $retryLimit = (int) $retryLimitSetting;

                    $record = AddressVerification::create([
                        'user_id' => $request->user() ? $request->user()->id : null,
                        'reference' => $reference,
                        'amount' => $amount,
                        'status' => 'success',
                        'longitude' => $longitude,
                        'latitude' => $latitude,
                        'paystack_data' => $paymentData,
                        'retry_limit' => $retryLimit,
                        'retry_count' => 0
                    ]);
                } else {
                    $record->update([
                        'status' => 'success',
                        'paystack_data' => $paymentData
                    ]);
                }

                $widgetId = config('services.dojah.widget_id', '6a33ca593c44efdbfa8c48c4');
                $dojahUrl = "https://identity.dojah.io?widget_id={$widgetId}&metadata[payment_reference]={$reference}";

                return ResponseHelper::withSuccess('Payment verified successfully. Redirect to Dojah widget.', [
                    'authorization_url' => $dojahUrl,
                    'reference' => $reference,
                    'verified_address' => false,
                    'retry_count' => $record->retry_count,
                    'retry_limit' => $record->retry_limit,
                    'verification_message' => $record->verification_message,
                ]);
            }

            return ResponseHelper::withError('Payment not successful');
        } catch (\Exception $e) {
            return ResponseHelper::withError("Verification failed", [
                'message' => $e->getMessage()
            ]);
        }
    }
}
