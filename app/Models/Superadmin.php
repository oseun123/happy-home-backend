<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Superadmin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'superadmins';

    protected $fillable = [
        'email',
        'password',
        'reset_token',
        'reset_expires_at'
    ];

    protected $hidden = ['password'];
}
