<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DojahWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $this->verifySignature($request);

        $payload = $request->all();

        Log::channel('daily')->info('Dojah Webhook Received', $payload);

        $eventType = $payload['type'] ?? null;

        // match() replaced with if/elseif for PHP 7 compatibility
        if ($eventType === 'address.verification') {
            $this->handleAddressVerification($payload);
        } else {
            Log::warning('Dojah: Unhandled event', ['type' => $eventType]);
        }

        return response()->json(['status' => 'received'], 200);
    }

    private function handleAddressVerification(array $payload)
    {
        $data = $payload['data'] ?? [];
        $referenceId = $data['reference_id'] ?? null;
        $status = $data['status'] ?? null;

        Log::info('Address Verification Update', [
            'reference_id' => $referenceId,
            'status'       => $status,
        ]);

        // KycVerification::where('reference_id', $referenceId)->update(['status' => $status]);
    }

    private function verifySignature(Request $request)
    {
        $secret = config('services.dojah.webhook_secret');

        if ($request->query('secret') !== $secret) {
            abort(403, 'Unauthorized webhook request');
        }
    }
}
