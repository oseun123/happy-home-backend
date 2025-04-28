<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddressVerification extends Model
{
    use HasFactory;

    protected $table = 'user_address_verification_payments';

    protected $fillable = [
        'user_id',
        'reference',
        'amount',
        'status', // pending, success, failed
        'longitude',
        'latitude',
        'verified_address',
        'paystack_data',
        'dojah_data',
    ];

    protected $casts = [
        'paystack_data' => 'array',
        'dojah_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
