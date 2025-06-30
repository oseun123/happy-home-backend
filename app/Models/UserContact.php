<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserContact extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'house_number',
        'street_name',
        'land_mark',
        'lga',
        'postal_code',
        'state',
        'nationality',
    ];

    protected $hidden = [
        'house_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
