<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscriber_id',
        'subscribed_to_id',
        'amount_paid',
        'verified',
        'verified_at',
        'subscribed_at',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'subscribed_at' => 'datetime',
    ];

    public function subscriber()
    {
        return $this->belongsTo(User::class, 'subscriber_id');
    }

    public function subscribedTo()
    {
        return $this->belongsTo(User::class, 'subscribed_to_id');
    }
}
