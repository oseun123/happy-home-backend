<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\FreeRetryAvailableNotification;
use App\Notifications\ReminderToReciprocateNotification;

class CheckUnreciprocatedSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-unreciprocated';
    protected $description = 'Check unreciprocated subscriptions and grant free retries';

    public function handle()
    {
        $now = Carbon::now();
        $tomorrow = $now->copy()->addDay();
        // 1. Send reminder 1 day before
        Subscription::where('fully_subscribed', false)
            ->whereBetween('reciprocation_deadline', [
                $tomorrow->copy()->startOfDay(),
                $tomorrow->copy()->endOfDay(),
            ])
            ->where('verified', true)
            ->each(function ($sub) {

                $subscribedTo = User::find($sub->subscribed_to_id);
                $subscriber = User::find($sub->subscriber_id);
                if ($subscribedTo && $subscriber) {
                    $subscribedTo->notify(new ReminderToReciprocateNotification($subscriber));
                }
            });

        // 2. Handle missed reciprocation
        Subscription::where('fully_subscribed', false)
            ->where('reciprocation_deadline', '<', $now)
            ->where('free_retry_granted', false)
            ->where('verified', true)
            ->get()
            ->each(function ($sub) {
                $subscriber = User::find($sub->subscriber_id);
                $subscribedTo = User::find($sub->subscribed_to_id);

                if ($subscriber && $subscribedTo) {
                    $sub->update([
                        'free_retry_granted' => true,
                        'free_retry_available_at' => now(),
                    ]);

                    // Delete the old subscription (unsubscribe)
                    $sub->delete();

                    // Notify subscriber about free retry
                    $subscriber->notify(new FreeRetryAvailableNotification($subscribedTo));
                }
            });

        $this->info('Checked subscriptions and processed free retries.');
    }
}
