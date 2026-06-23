<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\AddressVerification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Notifications\SubscribedNotification;
use App\Notifications\SubscriptionConfirmedNotification;
use App\Notifications\AddressVerifiedNotification;

class VerifyPendingPayments extends Command
{
    protected $signature = 'payments:verify-pending';
    protected $description = 'Retry verification for any payments that were debited but not confirmed due to network issues';

    public function handle()
    {
        $this->info('Starting pending payment verification...');

        $this->verifyPendingSubscriptions();
        $this->verifyPendingAddressVerifications();

        $this->info('Done.');
    }

    /**
     * Find all unverified subscriptions created in the last 24 hours
     * that have a reference (meaning a payment was initiated) and retry verification.
     */
    private function verifyPendingSubscriptions(): void
    {
        $pending = Subscription::where('verified', false)
            ->whereNotNull('reference')
            // Exclude records already stamped as abandoned or failed — no point retrying
            ->whereNotIn('payment_status', ['abandoned', 'failed', 'cancelled'])
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->get();

        if ($pending->isEmpty()) {
            $this->line('  [Subscriptions] No pending subscriptions found.');
            return;
        }

        $this->line("  [Subscriptions] Found {$pending->count()} pending subscription(s). Verifying...");

        foreach ($pending as $subscription) {
            $this->verifySubscriptionReference($subscription);
        }
    }

    /**
     * Find all pending address verifications created in the last 24 hours and retry.
     */
    private function verifyPendingAddressVerifications(): void
    {
        $pending = AddressVerification::where('status', 'pending')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->get();

        if ($pending->isEmpty()) {
            $this->line('  [Address] No pending address verifications found.');
            return;
        }

        $this->line("  [Address] Found {$pending->count()} pending address verification(s). Verifying...");

        foreach ($pending as $record) {
            $this->verifyAddressReference($record);
        }
    }

    /**
     * Call Paystack to verify a single subscription reference.
     */
    private function verifySubscriptionReference(Subscription $subscription): void
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('paystack.secretKey'),
                'Accept'        => 'application/json',
            ])->get("https://api.paystack.co/transaction/verify/{$subscription->reference}");

            $data           = $response->json();
            $paystackStatus = $data['data']['status'] ?? null;

            // Stamp the payment_status regardless of outcome for full audit trail
            // and to prevent re-querying known-failed records in future cycles
            if (in_array($paystackStatus, ['abandoned', 'failed', 'cancelled'])) {
                $subscription->update(['payment_status' => $paystackStatus]);
                $this->line("    ✗ Reference [{$subscription->reference}] is '{$paystackStatus}' on Paystack. Stamped and excluded from future retries.");
                return;
            }

            if ($paystackStatus !== 'success') {
                $this->line("    ⚠ Reference [{$subscription->reference}] status is '{$paystackStatus}'. Skipping for now.");
                return;
            }

            // Payment confirmed — update the subscription record
            $subscription->update([
                'amount_paid'            => $data['data']['amount'],
                'verified'               => true,
                'payment_status'         => 'success',
                'verified_at'            => now(),
                'subscribed_at'          => now(),
                'reciprocation_deadline' => now()->addDays(
                    (int) (Setting::where('key', 'subscribe_day')->value('value')[0] ?? 3)
                ),
                'data' => json_encode($data['data']),
            ]);

            // Check for mutual subscription
            $mutual = Subscription::where('subscriber_id', $subscription->subscribed_to_id)
                ->where('subscribed_to_id', $subscription->subscriber_id)
                ->where('verified', true)
                ->first();

            if ($mutual) {
                $subscription->update(['fully_subscribed' => true]);
                $mutual->update(['fully_subscribed' => true]);
            }

            // Notify both parties
            $subscriber     = User::find($subscription->subscriber_id);
            $subscribedUser = User::find($subscription->subscribed_to_id);

            if ($subscriber && $subscribedUser) {
                $subscribedUser->notify(new SubscribedNotification($subscriber));
                $subscriber->notify(new SubscriptionConfirmedNotification($subscribedUser));
            }

            $this->line("    ✅ Subscription [{$subscription->reference}] verified successfully.");
            Log::info("[payments:verify-pending] Subscription verified: {$subscription->reference}");
        } catch (\Exception $e) {
            $this->line("    ✗ Error verifying subscription [{$subscription->reference}]: {$e->getMessage()}");
            Log::error("[payments:verify-pending] Subscription error: {$subscription->reference}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Call Paystack to verify a single address verification reference.
     */
    private function verifyAddressReference(AddressVerification $record): void
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('paystack.secretKey'),
                'Accept'        => 'application/json',
            ])->get("https://api.paystack.co/transaction/verify/{$record->reference}");

            $paymentData = $response->json();

            if (($paymentData['data']['status'] ?? null) !== 'success') {
                $this->line("    ⚠ Reference [{$record->reference}] not yet successful on Paystack. Skipping.");
                return;
            }

            // Payment confirmed — update status
            $record->update([
                'status'       => 'success',
                'paystack_data' => $paymentData,
            ]);

            $this->line("    ✅ Address Verification payment [{$record->reference}] verified successfully. Awaiting Dojah widget verification.");
            Log::info("[payments:verify-pending] Address verification payment verified: {$record->reference}");
        } catch (\Exception $e) {
            $this->line("    ✗ Error verifying address [{$record->reference}]: {$e->getMessage()}");
            Log::error("[payments:verify-pending] Address error: {$record->reference}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
