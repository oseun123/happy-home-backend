<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class UserHobbiesInterest extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = ['user_id', 'hobbies', 'interest', 'language_spoken'];

    protected $casts = [
        'hobbies' => 'array',
        'interest' => 'array',
        'language_spoken' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
