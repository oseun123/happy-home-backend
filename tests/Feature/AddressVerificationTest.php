<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AddressVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_checks_if_user_has_verified_address_correctly()
    {
        // 1. Create a user
        $user = User::factory()->create();

        // 2. Initially, user has no address verifications, so should return false
        $this->assertFalse($user->hasVerifiedAddress());

        // 3. Create a pending/failed address verification
        $verification1 = AddressVerification::create([
            'user_id' => $user->id,
            'reference' => 'ref_1',
            'amount' => 500,
            'status' => 'pending',
            'longitude' => '3.3903',
            'latitude' => '6.4474',
            'verified_address' => 0,
        ]);

        // Reload latest relationship / check hasVerifiedAddress
        $user->load('latestAddressVerification');
        $this->assertFalse($user->hasVerifiedAddress());

        // 4. Create a successful verification record
        $verification2 = AddressVerification::create([
            'user_id' => $user->id,
            'reference' => 'ref_2',
            'amount' => 500,
            'status' => 'success',
            'longitude' => '3.3903',
            'latitude' => '6.4474',
            'verified_address' => 1,
        ]);

        // Reload relation
        $user->load('latestAddressVerification');
        $this->assertTrue($user->hasVerifiedAddress());
    }

    /** @test */
    public function it_sends_notification_on_failed_webhook()
    {
        \Illuminate\Support\Facades\Notification::fake();

        // 1. Create a user
        $user = User::factory()->create();

        // 2. Create an address verification record
        $verification = AddressVerification::create([
            'user_id' => $user->id,
            'reference' => 'test_ref_failed',
            'amount' => 500,
            'status' => 'success', // paystack payment success
            'longitude' => '3.3903',
            'latitude' => '6.4474',
            'verified_address' => 0,
            'retry_limit' => 3,
            'retry_count' => 0,
        ]);

        // 3. Trigger the webhook
        $payload = [
            'status' => false,
            'message' => 'Coordinates do not match.',
            'reference_id' => 'dojah_ref_123',
            'verification_status' => 'Failed',
            'metadata' => [
                'payment_reference' => 'test_ref_failed'
            ]
        ];

        $response = $this->postJson('/api/webhooks/dojah', $payload);

        $response->assertStatus(200);

        // Verify database is updated
        $verification->refresh();
        $this->assertEquals(1, $verification->retry_count);
        $this->assertEquals('Failed', $verification->dojah_verification_status);
        $this->assertEquals('Coordinates do not match.', $verification->verification_message);

        // Assert notification was sent
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $user,
            \App\Notifications\AddressVerificationFailedNotification::class,
            function ($notification) use ($verification) {
                $mailData = $notification->toMail($verification->user);
                $this->assertEquals('Address Verification Failed', $mailData->subject);
                // Check if the mail content includes retry count left (3 - 1 = 2)
                $rendered = strip_tags($mailData->render());
                $this->assertStringContainsString('2 attempts left', $rendered);
                $this->assertStringContainsString('Coordinates do not match.', $rendered);
                $this->assertStringContainsString('/user/transactions', $rendered);
                return true;
            }
        );
    }
}

