<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class User extends Authenticatable implements AuditableContract
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'verification_token',
        'verification_expires_at',
        'is_verified',
        'reset_token',
        'reset_expires_at',
        'deletion_requested',
        'scheduled_deletion_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_verified' => 'boolean',
        'verification_expires_at' => 'datetime',
    ];



    public function personalProfile()
    {
        return $this->hasOne(PersonalProfile::class);
    }

    public function userBioData()
    {
        return $this->hasOne(UserBioData::class);
    }

    public function contact()
    {
        return $this->hasOne(UserContact::class);
    }

    public function hobbiesInterest()
    {
        return $this->hasOne(UserHobbiesInterest::class);
    }
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function preferredMatches()
    {
        return $this->hasMany(UserPreferredMatch::class);
    }


    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites', 'user_id', 'favorite_user_id')
            ->withTimestamps();
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites', 'favorite_user_id', 'user_id')
            ->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'subscriber_id', 'subscribed_to_id')
            ->withPivot(['amount_paid', 'verified', 'verified_at', 'subscribed_at'])
            ->wherePivot('verified', true)
            ->withTimestamps();
    }



    // Users who subscribed to me
    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'subscribed_to_id', 'subscriber_id')
            ->withPivot(['amount_paid', 'verified', 'verified_at', 'subscribed_at'])
            ->wherePivot('verified', true)
            ->withTimestamps();
    }


    public function hasSubscribedTo(User $otherUser)
    {
        return $this->subscriptions()
            ->where('subscribed_to_id', $otherUser->id)
            ->where('verified', true)
            ->exists();
    }

    public function isSubscribedBy(User $otherUser)
    {
        return $this->subscribers()
            ->where('subscriber_id', $otherUser->id)
            ->where('verified', true)
            ->exists();
    }

    public function isMutuallySubscribedWith(User $otherUser)
    {
        return $this->hasSubscribedTo($otherUser) && $this->isSubscribedBy($otherUser);
    }

    public function hasFavorited(User $otherUser)
    {
        return $this->favorites()
            ->where('favorite_user_id', $otherUser->id)
            ->exists();
    }
    public function hasBlocked(User $otherUser): bool
    {
        if (!$this->isMutuallySubscribedWith($otherUser)) {
            return false;
        }

        return Subscription::where('subscriber_id', $this->id)
            ->where('subscribed_to_id', $otherUser->id)
            ->where('verified', true)
            ->where('is_blocked', true)
            ->exists();
    }


    public function isFavoritedBy(User $otherUser)
    {
        return $this->favoritedBy()
            ->where('user_id', $otherUser->id)
            ->exists();
    }
    public function subscriptionRecords()
    {
        return $this->hasMany(Subscription::class, 'subscriber_id');
    }

    public function availableFreeRetry()
    {
        return $this->subscriptionRecords()
            ->onlyTrashed()
            ->where('free_retry_granted', true)
            ->where('free_retry_used', false)
            ->orderByDesc('free_retry_available_at')
            ->first();
    }

    public function countAvailableRetries()
    {
        return $this->subscriptionRecords()
            ->onlyTrashed()
            ->where('free_retry_granted', true)
            ->where('free_retry_used', false)
            ->count();
    }

    public function totalRetriesUsed()
    {
        return $this->subscriptionRecords()
            ->where('is_free_retry', true)
            ->count();
    }

    public function addressVerifications()
    {
        return $this->hasMany(AddressVerification::class);
    }

    public function latestAddressVerification()
    {
        return $this->hasOne(AddressVerification::class)->latestOfMany();
    }

    public function hasVerifiedAddress(): bool
    {
        return $this->latestAddressVerification !== null;
    }

    public function daysUntilDeletion()
    {
        // if deletion not requested or no scheduled date, bail out
        if (! $this->deletion_requested || ! $this->scheduled_deletion_at) {
            return false;
        }

        $now  = Carbon::now();
        $then = Carbon::parse($this->scheduled_deletion_at);

        // if the scheduled date is in the past, no days left
        if ($now->greaterThanOrEqualTo($then)) {
            return false;
        }

        // diffInDays gives whole days between now and then
        return $now->diffInDays($then);
    }
}
