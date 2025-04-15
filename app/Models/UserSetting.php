<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class UserSetting extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'hide_age',
        'hide_location',
        'matchup_settings',
        'notify_me',
        'deactive_account',
        'short_bio',
        'cover_photo',
        'photo_1',
        'photo_2',
        'photo_3',
        'photo_4',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
