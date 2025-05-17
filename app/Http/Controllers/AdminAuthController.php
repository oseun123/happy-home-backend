<?php

namespace App\Http\Controllers;

use App\Models\Superadmin;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Notifications\SuperAdminNotification;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Find the superadmin by email
        $superAdmin = Superadmin::where('email', $request->email)->first();


        // Manually check the password since Sanctum does not support `attempt()`
        if (!$superAdmin || !Hash::check($request->password, $superAdmin->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate a Sanctum token
        $token = $superAdmin->createToken('SuperAdmin-API-Token')->plainTextToken;

        return ResponseHelper::withSuccess('Login successful', ['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return ResponseHelper::withSuccess('Logged out');
    }

    /**
     * Forgot Password: Generate a reset token and send to email
     */
    public function forgotPassword(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:superadmins,email',
        ]);

        $superAdmin = Superadmin::where('email', $request->email)->first();


        // Generate a random token
        $resetToken = Str::random(64);

        // Save token and expiration
        $superAdmin->update([
            'reset_token' => $resetToken,
            'reset_expires_at' => now()->addHour(), // Expire in 1 hour
        ]);

        // Send reset token via email
        $message = "You requested a password reset. Click the button below to reset your password.";
        $resetLink = env('FRONTEND_URL') . '/super-admin/reset-password' . '?token=' . $resetToken . '&email=' . $superAdmin->email;
        $superAdmin->notify(new SuperAdminNotification('Reset Password Link', $message, "Reset Password", $resetLink));

        return ResponseHelper::withSuccess('A password reset link has been sent to your email.');
    }

    /**
     * Reset Password: Validate reset token and change password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:superadmins,email',
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $superAdmin = SuperAdmin::where('email', $request->email)
            ->where('reset_token', $request->reset_token)
            ->first();

        if (!$superAdmin) {
            return ResponseHelper::withError('Invalid or expired reset token.');
        }

        if ($superAdmin->reset_expires_at && now()->gt($superAdmin->reset_expires_at)) {
            return ResponseHelper::withError('Reset token has expired.');
        }

        // Reset password
        $superAdmin->update([
            'password' => Hash::make($request->password),
            'reset_token' => null, // Invalidate token after use
            'reset_expires_at' => null,
        ]);

        return ResponseHelper::withSuccess('Password reset successful. You can now log in.');
    }
}
