<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Http;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Notifications\SubscribedNotification;

class SubscriptionController extends Controller
{
    //

    public function initializePayment(Request $request, User $user)
    {
        $request->validate([
            'subscribed_to_id' => 'required|exists:users,id'
        ]);

        $subscriber = $user;
        $subscribedToId = $request->subscribed_to_id;

        if ($subscriber->id == $subscribedToId) {
            return ResponseHelper::withError('You cannot subscribe to yourself');
        }

        // Check if already subscribed and verified
        $alreadySubscribed = Subscription::where('subscriber_id', $subscriber->id)
            ->where('subscribed_to_id', $subscribedToId)
            ->where('verified', true)
            ->exists();

        if ($alreadySubscribed) {
            return ResponseHelper::withError('Already subscribed');
        }
        // Get subscribe amount from settings
        $amountSetting = Setting::where('key', 'subscribe_amount')->value('value')[0];
        $amountInKobo = (int) $amountSetting * 100;

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        $data = [
            "amount" => $amountInKobo, // Paystack expects amount in kobo
            "email" => $subscriber->email,
            "currency" => "NGN",
            "reference" => Paystack::genTranxRef(),
            "callback_url" => $frontendUrl . '/verify-payment',
            'metadata' => [
                'subscriber_id' => $subscriber->id,
                'subscribed_to_id' => $subscribedToId,
            ]
        ];

        try {
            $response = Paystack::getAuthorizationUrl($data)->url;
            return ResponseHelper::withSuccess("Initialize successfully", ['status' => 'success', 'authorization_url' => $response]);
        } catch (\Exception $e) {
            return ResponseHelper::withError('Failed to initialize', ['status' => 'error', 'message' => 'Payment initialization failed']);
        }
    }





    public function verifyPayment(Request $request)
    {
        $request->validate(['reference' => 'required|string']);

        $reference = $request->reference;
        $paystackSecretKey = config('paystack.secretKey');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $paystackSecretKey,
                'Accept' => 'application/json',
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $paymentDetails = $response->json();

            if ($paymentDetails['data']['status'] === 'success') {
                $data = $paymentDetails['data'];

                $subscriberId = $data['metadata']['subscriber_id'];
                $subscribedToId = $data['metadata']['subscribed_to_id'];

                $subscription = Subscription::create([
                    'subscriber_id' => $subscriberId,
                    'subscribed_to_id' => $subscribedToId,
                    'amount_paid' => $data['amount'],
                    'verified' => true,
                    'verified_at' => now(),
                    'subscribed_at' => now(),
                    'fully_subscribed' => false,
                ]);

                // Check if the subscribed user has also subscribed back
                $mutual = Subscription::where('subscriber_id', $subscribedToId)
                    ->where('subscribed_to_id', $subscriberId)
                    ->where('verified', true)
                    ->first();

                if ($mutual) {
                    Subscription::where('id', $subscription->id)->update(['fully_subscribed' => true]);
                    Subscription::where('id', $mutual->id)->update(['fully_subscribed' => true]);
                }

                // Notify the subscribed user
                $subscribedUser = User::find($subscribedToId);
                $subscriber = User::find($subscriberId);

                $subscribedUser->notify(new SubscribedNotification($subscriber));

                return ResponseHelper::withSuccess('Payment verified', [
                    'status' => 'success',
                    'data' => $paymentDetails
                ]);
            }

            return ResponseHelper::withError('Payment failed', [
                'status' => 'failed',
                'message' => $paymentDetails['message'] ?? 'Transaction not successful'
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::withError('Verification failed', [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
