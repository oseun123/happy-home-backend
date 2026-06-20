<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlacklistedPhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'attempts',
        'is_blacklisted',
        'reason',
        'blacklisted_at'
    ];

    protected $casts = [
        'is_blacklisted' => 'boolean',
        'blacklisted_at' => 'datetime'
    ];
}
