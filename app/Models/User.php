<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

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
        'reset_expires_at'
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
}
