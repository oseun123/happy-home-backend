<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreferredMatchReligion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_preferred_match_id',
        'religion',
        'denomination'
    ];

    public function preferredMatch()
    {
        return $this->belongsTo(UserPreferredMatch::class);
    }
}
