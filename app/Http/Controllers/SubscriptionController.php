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
use App\Notifications\DeclineRequestNotification;
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
        \Log::info('Verify payment request', [
            'reference' => $reference,
            'paystackSecretKey' => $paystackSecretKey
        ]);

        // Check if this reference has already been successfully verified
        $existingSubscription = Subscription::where('reference', $reference)
            ->where('payment_status', 'success')
            ->first();

        if ($existingSubscription) {
            return ResponseHelper::withSuccess('Payment verified', [
                'status' => 'success',
                'data' => json_decode($existingSubscription->data, true)
            ]);
        }

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
                    'amount_paid'            => $data['amount'],
                    'verified'               => true,
                    'payment_status'         => 'success',
                    'verified_at'            => now(),
                    'subscribed_at'          => now(),
                    'reciprocation_deadline' => now()->addDays((int) Setting::where('key', 'subscribe_day')->value('value')[0] ?? 3),
                    'data'                   => json_encode($paymentDetails['data']),
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

    public function delineRequest(Request $request, User $user, User $subscribedTo)
    {

        // Ensure subscription exists
        $sub = Subscription::where('subscriber_id', $subscribedTo->id)
            ->where('subscribed_to_id', $user->id)
            ->where('verified', true)
            ->first();

        if (!$sub) {
            return ResponseHelper::withError('Can only delicine users subscribed to you.');
        }

        // delcine request
        $sub->update([
            'free_retry_granted' => true,
            'free_retry_available_at' => now(),
        ]);

        // Delete the old subscription (unsubscribe)
        $sub->delete();

        // Notify subscriber about free retry
        $subscribedTo->notify(new DeclineRequestNotification($user));

        return ResponseHelper::withSuccess("User successfully declined.");
    }

    /**
     * GET /paystack/transactions
     * Returns a combined, chronologically sorted transaction history for the authenticated user.
     * Includes both subscription payments and address verification payments.
     */
    public function transactionHistory(Request $request)
    {
        $user = $request->user();

        // Fetch subscription payments for this user
        $subscriptions = Subscription::where('subscriber_id', $user->id)
            ->whereNotNull('reference')
            ->get()
            ->map(function ($sub) {
                return [
                    'type'           => 'subscription',
                    'reference'      => $sub->reference,
                    'amount'         => $sub->amount_paid ? ($sub->amount_paid) / 100 : 0.00,
                    'payment_status' => $sub->payment_status ?? ($sub->verified ? 'success' : 'pending'),
                    'verified'       => $sub->verified,
                    'verified_at'    => $sub->verified_at,
                    'date'           => $sub->created_at,
                    'description'    => 'Subscription payment',
                    'meta'           => [
                        'subscribed_to_id' => $sub->subscribed_to_id,
                        'is_free_retry'    => $sub->is_free_retry,
                    ],
                ];
            });

        // Fetch address verification payments for this user
        $addressVerifications = \App\Models\AddressVerification::where('user_id', $user->id)
            ->whereNotNull('reference')
            ->get()
            ->map(function ($av) {
                return [
                    'type'           => 'address_verification',
                    'reference'      => $av->reference,
                    'amount'         => $av->amount,
                    'payment_status' => $av->status,
                    'verified'       => $av->verified_address,
                    'verified_at'    => $av->updated_at,
                    'date'           => $av->created_at,
                    'description'    => 'Address verification payment',
                    'meta'           => [],
                ];
            });

        // Merge both collections and sort by date descending (most recent first)
        $transactions = $subscriptions
            ->concat($addressVerifications)
            ->sortByDesc('date')
            ->values();

        return ResponseHelper::withSuccess('Transaction history retrieved.', $transactions);
    }
}
