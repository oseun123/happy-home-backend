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
}
