<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\Setting;
use App\Notifications\AddressVerificationReviewNotification;
use App\Notifications\AddressVerificationPendingNotification;

class DojahWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // $this->verifySignature($request);

        $payload = $request->all();

        Log::channel('daily')->info('Dojah Webhook Received', $payload);

        $paymentReference = $payload['metadata']['payment_reference'] ?? null;

        if ($paymentReference) {
            $this->handleAddressVerification($payload, $paymentReference);
        } else {
            Log::warning('Dojah: Webhook received without payment_reference', $payload);
        }

        return response()->json(['status' => 'received'], 200);
    }

    private function handleAddressVerification(array $payload, string $paymentReference)
    {
        $record = \App\Models\AddressVerification::where('reference', $paymentReference)->first();

        if (!$record) {
            Log::error('Dojah Webhook: AddressVerification record not found for reference ' . $paymentReference);
            return;
        }

        $status = $payload['status'] ?? false;
        $message = $payload['message'] ?? 'Address verification failed';
        $referenceId = $payload['reference_id'] ?? null;
        $verificationStatus = $payload['verification_status'] ?? null;

        Log::info('Dojah Webhook processing', [
            'reference' => $paymentReference,
            'status' => $status,
            'message' => $message,
            'reference_id' => $referenceId,
            'verification_status' => $verificationStatus
        ]);

        if ($status === true) {
            // Successfully verified!
            $record->update([
                'verified_address' => 1,
                'dojah_data' => $payload,
                'dojah_reference_id' => $referenceId,
                'dojah_verification_status' => $verificationStatus,
                'verification_message' => $message
            ]);

            // Notify user
            if ($record->user) {
                $record->user->notify(new \App\Notifications\AddressVerifiedNotification());
            }
        } else {
            // Failed or Pending verification
            // Only increment retry_count if the reference_id is DIFFERENT from what we have.
            // If it is the same, it means this is a duplicate or status update webhook for the same attempt.
            $newRetryCount = $record->retry_count;
            if ($referenceId && $record->dojah_reference_id !== $referenceId) {
                $newRetryCount += 1;
            }

            $record->update([
                'verified_address' => 0,
                'dojah_data' => $payload,
                'dojah_reference_id' => $referenceId,
                'dojah_verification_status' => $verificationStatus,
                'verification_message' => $message,
                'retry_count' => $newRetryCount
            ]);

            // If verification status is Pending, send review emails
            if (strtolower($verificationStatus) === 'pending') {
                $this->sendPendingReviewEmails($record, $payload);
            }
        }
    }

    /**
     * Send review notification emails to admins and a pending notification to the user.
     */
    private function sendPendingReviewEmails($record, array $payload)
    {
        // Get admin emails from settings, default to Info@happyhomecreators.com
        $adminEmails = $this->getAdminReviewEmails();

        // Send review notification to each admin email
        foreach ($adminEmails as $email) {
            Log::info('Dojah Webhook: Sending review notification to admin', [
                'reference' => $record->reference,
                'admin_email' => $email,
            ]);
            Notification::route('mail', $email)
                ->notify(new AddressVerificationReviewNotification($record, $payload));
        }

        Log::info('Dojah Webhook: Pending review emails sent to admins', [
            'reference' => $record->reference,
            'admin_emails' => $adminEmails,
        ]);

        // Send pending notification to the user
        if ($record->user) {
            $record->user->notify(new AddressVerificationPendingNotification($payload));

            Log::info('Dojah Webhook: Pending notification sent to user', [
                'reference' => $record->reference,
                'user_id' => $record->user->id,
            ]);
        }
    }

    /**
     * Retrieve admin review email addresses from settings.
     * Setting key: 'address_verification_review_emails'
     * Falls back to default: Info@happyhomecreators.com
     */
    private function getAdminReviewEmails(): array
    {
        $defaultEmail = 'Info@happyhomecreators.com';

        $setting = Setting::where('key', 'address_verification_review_emails')->first();

        if ($setting && !empty($setting->value)) {
            // value is cast to array by the Setting model
            $emails = is_array($setting->value) ? $setting->value : [$setting->value];

            // Filter out any empty values
            $emails = array_filter($emails, fn($e) => !empty(trim($e)));

            return !empty($emails) ? array_values($emails) : [$defaultEmail];
        }

        return [$defaultEmail];
    }

    private function verifySignature(Request $request)
    {
        $secret = config('services.dojah.webhook_secret');

        if ($request->query('secret') !== $secret && $request->input('secret') !== $secret) {
            abort(403, 'Unauthorized webhook request');
        }
    }
}
