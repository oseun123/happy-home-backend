<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nudge extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'count',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
