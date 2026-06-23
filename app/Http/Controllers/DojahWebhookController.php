<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            // Failed verification
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
        }
    }

    private function verifySignature(Request $request)
    {
        $secret = config('services.dojah.webhook_secret');

        if ($request->query('secret') !== $secret && $request->input('secret') !== $secret) {
            abort(403, 'Unauthorized webhook request');
        }
    }
}
