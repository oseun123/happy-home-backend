<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerificationTokenNotification;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
            'password' => 'required|string|min:8|confirmed',
            'accept_terms' => 'required|boolean|in:1,true',
        ]);

        $user = User::withTrashed()->where('email', $request->email)->first();

        // Generate a 6-digit verification token
        $verificationToken = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        if ($user) {
            $user->restore();
            $user->update([
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'verification_token' => $verificationToken,
                'verification_expires_at' => now()->addHour(), // Expire in 1 hour
                'is_verified' => false,
            ]);
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'verification_token' => $verificationToken,
                'verification_expires_at' => now()->addHour(), // Expire in 1 hour
                'is_verified' => false,
            ]);
        }

        // Send the verification token via notification
        $user->notify(new VerificationTokenNotification($verificationToken));

        return ResponseHelper::withSuccess(
            'Registration successful! A verification code has been sent.'
        );
    }
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'verification_token' => 'required|string|min:6|max:6',
        ]);

        $user = User::where('email', $request->email)
            ->where('verification_token', $request->verification_token)
            ->first();

        if (!$user) {
            return ResponseHelper::withError('Invalid or expired verification code.');
        }

        if ($user->verification_expires_at && now()->gt($user->verification_expires_at)) {
            return ResponseHelper::withError('Verification token has expired.');
        }

        // Mark user as verified
        $user->update([
            'is_verified' => true,
            'verification_token' => null,
            'verification_expires_at' => null,
        ]);

        // Create a new authentication token
        $token = $user->createToken('User-API-Token')->plainTextToken;

        return ResponseHelper::withSuccess(
            'Account verified successfully. You are now logged in.',
            [
                'token' => $token,
                'profile' => (new UserProfileController)->userProfileLogin($user, $user->id)

            ]
        );
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return ResponseHelper::withSuccess(
            'Login successfully',
            [
                'token' => $request->user()->createToken('User-API-Token')->plainTextToken,
                'profile' => (new UserProfileController)->userProfileLogin($request->user(), $request->user()->id)
            ]
        );
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
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate a random token
        $resetToken = Str::random(64);

        // Save token and expiration
        $user->update([
            'reset_token' => $resetToken,
            'reset_expires_at' => now()->addHour(), // Expire in 1 hour
        ]);

        // Send reset token via email
        $user->notify(new ResetPasswordNotification($resetToken));

        return ResponseHelper::withSuccess('A password reset link has been sent to your email.');
    }

    /**
     * Reset Password: Validate reset token and change password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)
            ->where('reset_token', $request->reset_token)
            ->first();


        if (!$user) {
            return ResponseHelper::withError('Invalid or expired reset token.');
        }

        if ($user->reset_expires_at && now()->gt($user->reset_expires_at)) {
            return ResponseHelper::withError('Reset token has expired.');
        }

        // Reset password
        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null, // Invalidate token after use
            'reset_expires_at' => null,
        ]);

        return ResponseHelper::withSuccess('Password reset successful. You can now log in.');
    }
}
