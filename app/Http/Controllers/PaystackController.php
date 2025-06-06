<?php



namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaystackController extends Controller
{
    /**
     * Initialize a Payment Request
     */
    public function initializePayment(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        $data = [
            "amount" => $request->amount * 100, // Paystack expects amount in kobo
            "email" => $user->email,
            "currency" => "NGN",
            "reference" => Paystack::genTranxRef(),
            "callback_url" => $frontendUrl . '/verify-payment',
        ];

        try {
            $response = Paystack::getAuthorizationUrl($data)->url;
            return ResponseHelper::withSuccess("Initialize successfully", ['status' => 'success', 'authorization_url' => $response]);
        } catch (\Exception $e) {
            return ResponseHelper::withError('Failed to initialize', ['status' => 'error', 'message' => 'Payment initialization failed']);
        }
    }


    /**
     * Verify Payment Manually
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $reference = $request->reference;
        $paystackSecretKey = config('paystack.secretKey'); // Ensure this is set in your .env

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $paystackSecretKey,
                'Accept' => 'application/json',
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $paymentDetails = $response->json();

            if ($response->successful() && $paymentDetails['data']['status'] === 'success') {
                return ResponseHelper::withSuccess('Payment verified', [
                    'status' => 'success',
                    'data' => $paymentDetails
                ]);
            } else {
                return ResponseHelper::withError('Payment failed', [
                    'status' => 'failed',
                    'message' => $paymentDetails['message'] ?? 'Transaction not successful'
                ]);
            }
        } catch (\Exception $e) {
            return ResponseHelper::withError('Verification failed', [
                'status' => 'error',
                'message' => 'Invalid reference or network issue'
            ]);
        }
    }
}
