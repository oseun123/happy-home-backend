<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Notifications\NudgeReminder;
use Illuminate\Support\Facades\Http;
use App\Notifications\SubscribedNotification;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Notifications\SubscriptionConfirmedNotification;

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

        //Check for available free retry
        $freeRetry = Subscription::withTrashed()->where('subscriber_id', $subscriber->id)
            ->where('fully_subscribed', false)
            ->where('verified', true)
            ->where('free_retry_used', false)
            ->where('free_retry_granted', true) // make sure this isn't already a free retry
            ->first();

        // return ($freeRetry);



        $isUsingFreeRetry = false;
        $amountInKobo = 0;

        if ($freeRetry) {
            $isUsingFreeRetry = true;
            // Mark the original subscription so we don't reuse it again
            $freeRetry->update(['free_retry_used' => true]);
        } else {
            // Regular flow with payment
            $amountSetting = (int) Setting::where('key', 'subscribe_amount')->value('value')[0];
            $amountInKobo = (int) $amountSetting * 100;
        }

        // Prepare metadata
        $metadata = [
            'subscriber_id' => $subscriber->id,
            'subscribed_to_id' => $subscribedToId,

        ];

        if ($isUsingFreeRetry) {
            // Skip payment step, return immediate approval

            $subscribeDays = (int) Setting::where('key', 'subscribe_day')->value('value')[0] ?? 3;

            $subscription = Subscription::create([
                'subscriber_id' => $subscriber->id,
                'subscribed_to_id' => $subscribedToId,
                'amount_paid' => 0,
                'verified' => true,
                'verified_at' => now(),
                'subscribed_at' => now(),
                'fully_subscribed' => false,
                'reciprocation_deadline' => now()->addDays($subscribeDays),
                'is_free_retry' => true,
            ]);


            // Notify the subscribed user
            $subscribedUser = User::find($subscribedToId);

            $subscribedUser->notify(new SubscribedNotification($subscriber));
            $subscriber->notify(new SubscriptionConfirmedNotification($subscribedUser));




            // Check mutuality
            $mutual = Subscription::where('subscriber_id', $subscribedToId)
                ->where('subscribed_to_id', $subscriber->id)
                ->where('verified', true)
                ->first();

            if ($mutual) {
                $subscription->update(['fully_subscribed' => true]);
                $mutual->update(['fully_subscribed' => true]);
            }

            // Notify subscribed user
            $subscribedUser = User::find($subscribedToId);
            $subscribedUser->notify(new SubscribedNotification($subscriber));

            return ResponseHelper::withSuccess("Subscribed using free retry");
        }

        // Continue with Paystack payment if not using free retry
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        $data = [
            "amount" => $amountInKobo,
            "email" => $subscriber->email,
            "currency" => "NGN",
            "reference" => Paystack::genTranxRef(),
            "callback_url" => $frontendUrl . '/verify-payment',
            'metadata' => $metadata
        ];

        try {
            $response = Paystack::getAuthorizationUrl($data)->url;
            return ResponseHelper::withSuccess("Initialize successfully", [
                'status' => 'success',
                'authorization_url' => $response
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::withError('Failed to initialize', [
                'status' => 'error',
                'message' => 'Payment initialization failed'
            ]);
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

                $subscribeDays = (int) Setting::where('key', 'subscribe_day')->value('value')[0] ?? 3;
                // $isFreeRetry = (bool) $data['metadata']['is_free_retry'] ?? false;
                // 'is_free_retry' => (bool) ($data['metadata']['is_free_retry'] ?? false),

                $subscription = Subscription::create([
                    'subscriber_id' => $subscriberId,
                    'subscribed_to_id' => $subscribedToId,
                    'amount_paid' => $data['amount'],
                    'verified' => true,
                    'verified_at' => now(),
                    'subscribed_at' => now(),
                    'fully_subscribed' => false,
                    'reciprocation_deadline' => now()->addDays($subscribeDays),
                    'data' => json_encode($paymentDetails['data'])
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
                $subscriber->notify(new SubscriptionConfirmedNotification($subscribedUser));

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


    public function sendNudge(Request $request, User $user)
    {
        $request->validate(['subscribed_to_id' => 'required|exists:users,id']);

        $subscriber = $user;
        $subscribedToId = $request->subscribed_to_id;

        $subscription = Subscription::where('subscriber_id', $subscriber->id)
            ->where('subscribed_to_id', $subscribedToId)
            ->where('verified', true)
            ->firstOrFail();

        $nudge = $subscription->nudge()->firstOrCreate([]);

        if ($nudge->count >= 2) {
            return ResponseHelper::withError('You can only nudge twice.');
        }

        $nudge->increment('count');

        $subscribedUser = User::findOrFail($subscribedToId);
        $subscribedUser->notify(new NudgeReminder($subscriber));

        return ResponseHelper::withSuccess('Nudge sent successfully.');
    }
}
