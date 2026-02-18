<?php

namespace App\Models;

use App\Models\Nudge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subscriber_id',
        'subscribed_to_id',
        'amount_paid',
        'reference',
        'payment_status',
        'verified',
        'verified_at',
        'subscribed_at',
        'fully_subscribed',
        'reciprocation_deadline',
        'is_free_retry',
        'free_retry_granted',
        'free_retry_used',
        'free_retry_available_at',
        'data'
    ];

    protected $casts = [
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'fully_subscribed' => 'boolean',
        'reciprocation_deadline' => 'datetime',
        'is_free_retry' => 'boolean',
        'free_retry_granted' => 'boolean',
        'free_retry_used' => 'boolean',
        'free_retry_available_at' => 'datetime',
        'deleted_at' => 'datetime',
        'value' => 'array', // Ensures values are stored as JSON but accessed as arrays

    ];

    public function subscriber()
    {
        return $this->belongsTo(User::class, 'subscriber_id');
    }

    public function subscribedTo()
    {
        return $this->belongsTo(User::class, 'subscribed_to_id');
    }

    public function nudge()
    {
        return $this->hasOne(Nudge::class);
    }
}
