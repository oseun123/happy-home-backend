<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Notifications\NudgeReminder;
use Illuminate\Support\Facades\Http;
use App\Notifications\BlockStatusChanged;
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

        $alreadySubscribed = Subscription::where('subscriber_id', $subscriber->id)
            ->where('subscribed_to_id', $subscribedToId)
            ->where('verified', true)
            ->exists();

        if ($alreadySubscribed) {
            return ResponseHelper::withError('Already subscribed');
        }

        $freeRetry = Subscription::withTrashed()->where('subscriber_id', $subscriber->id)
            ->where('fully_subscribed', false)
            ->where('verified', true)
            ->where('free_retry_used', false)
            ->where('free_retry_granted', true)
            ->first();

        $isUsingFreeRetry = false;
        $amountInKobo = 0;

        if ($freeRetry) {
            $isUsingFreeRetry = true;
            $freeRetry->update(['free_retry_used' => true]);
        } else {
            $amountSetting = (int) Setting::where('key', 'subscribe_amount')->value('value')[0];
            $amountInKobo = (int) $amountSetting * 100;
        }

        $subscribeDays = (int) Setting::where('key', 'subscribe_day')->value('value')[0] ?? 3;

        if ($isUsingFreeRetry) {
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

            $subscribedUser = User::find($subscribedToId);
            $subscribedUser->notify(new SubscribedNotification($subscriber));
            $subscriber->notify(new SubscriptionConfirmedNotification($subscribedUser));

            $mutual = Subscription::where('subscriber_id', $subscribedToId)
                ->where('subscribed_to_id', $subscriber->id)
                ->where('verified', true)
                ->first();

            if ($mutual) {
                $subscription->update(['fully_subscribed' => true]);
                $mutual->update(['fully_subscribed' => true]);
            }

            return ResponseHelper::withSuccess("Subscribed using free retry");
        }

        // Non-free: create a pending subscription
        $reference = Paystack::genTranxRef();

        Subscription::create([
            'subscriber_id' => $subscriber->id,
            'subscribed_to_id' => $subscribedToId,
            'reference' => $reference,
            'verified' => false,
            'amount_paid' => 0
        ]);

        $metadata = [
            'subscriber_id' => $subscriber->id,
            'subscribed_to_id' => $subscribedToId,
            'reference' => $reference,
        ];

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        $data = [
            "amount" => $amountInKobo,
            "email" => $subscriber->email,
            "currency" => "NGN",
            "reference" => $reference,
            "callback_url" => $frontendUrl . '/verify-payment',
            "metadata" => $metadata,
        ];

        try {
            $response = Paystack::getAuthorizationUrl($data)->url;
            return ResponseHelper::withSuccess("Initialized successfully", [
                'status' => 'success',
                'authorization_url' => $response
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::withError('Payment initialization failed', [
                'message' => $e->getMessage()
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
                $reference = $data['reference'];

                $subscription = Subscription::where('reference', $reference)->first();

                if (!$subscription) {
                    return ResponseHelper::withError("Subscription not found for reference.");
                }

                $subscription->update([
                    'amount_paid' => $data['amount'],
                    'verified' => true,
                    'verified_at' => now(),
                    'subscribed_at' => now(),
                    'reciprocation_deadline' => now()->addDays((int) Setting::where('key', 'subscribe_day')->value('value')[0] ?? 3),
                    'data' => json_encode($paymentDetails['data']),
                ]);

                $subscriber = User::find($subscription->subscriber_id);
                $subscribedUser = User::find($subscription->subscribed_to_id);

                // Mutual check
                $mutual = Subscription::where('subscriber_id', $subscription->subscribed_to_id)
                    ->where('subscribed_to_id', $subscription->subscriber_id)
                    ->where('verified', true)
                    ->first();

                if ($mutual) {
                    $subscription->update(['fully_subscribed' => true]);
                    $mutual->update(['fully_subscribed' => true]);
                }

                $subscribedUser->notify(new SubscribedNotification($subscriber));
                $subscriber->notify(new SubscriptionConfirmedNotification($subscribedUser));

                return ResponseHelper::withSuccess('Payment verified', [
                    'status' => 'success',
                    'data' => $paymentDetails
                ]);
            }

            return ResponseHelper::withError('Payment not successful', [
                'message' => $paymentDetails['message'] ?? 'Verification failed'
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::withError('Verification failed', [
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



    public function toggleBlock(Request $request, User $user, User $subscribedTo)
    {

        // Ensure mutual subscription exists
        $userSub = Subscription::where('subscriber_id', $user->id)
            ->where('subscribed_to_id', $subscribedTo->id)
            ->where('verified', true)
            ->where('fully_subscribed', true)
            ->first();

        $subToUser = Subscription::where('subscriber_id', $subscribedTo->id)
            ->where('subscribed_to_id', $user->id)
            ->where('verified', true)
            ->where('fully_subscribed', true)
            ->first();

        if (!$userSub || !$subToUser) {
            return ResponseHelper::withError('Blocking is only allowed between mutual subscriptions.');
        }

        // Toggle block
        $userSub->is_blocked = !$userSub->is_blocked;
        $userSub->save();

        $status = $userSub->is_blocked ? 'blocked' : 'unblocked';

        $subscribedTo->notify(new BlockStatusChanged($user, $userSub->is_blocked));

        return ResponseHelper::withSuccess("User successfully {$status}.");
    }
}
